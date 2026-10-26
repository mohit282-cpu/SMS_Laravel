<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\MyClass;
use App\Models\Section;
use App\Models\StudyMaterial;
use App\Models\Subject;
use App\Repositories\MyClassRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudyMaterialController extends Controller
{
    protected $my_class;

    public function __construct(MyClassRepo $my_class)
    {
        $this->middleware('auth');
        $this->my_class = $my_class;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = StudyMaterial::visibleTo($user)
            ->with(['teacher', 'my_class', 'section', 'subject'])
            ->orderBy('created_at', 'desc');

        if ($request->has('class_id') && $request->class_id) {
            $query->where('my_class_id', $request->class_id);
        }

        if ($request->has('subject_id') && $request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        $materials = $query->paginate(15);
        $classes = $this->my_class->all();

        $subjects = collect();
        if ($user->user_type === 'teacher') {
            $subjects = Subject::where('teacher_id', $user->id)->get();
        } elseif (in_array($user->user_type, ['super_admin', 'admin'])) {
            $subjects = Subject::all();
        }

        return view('pages.support_team.study_materials.index', compact('materials', 'classes', 'subjects'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->user_type, ['super_admin', 'admin', 'teacher'])) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'my_class_id' => 'required|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'material_file' => 'required|file|max:10240|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,zip,jpg,jpeg,png',
        ], [
            'material_file.mimes' => 'The file must be a document, presentation, spreadsheet, archive, or image (pdf, doc, docx, ppt, pptx, xls, xlsx, txt, zip, jpg, png).',
            'material_file.max' => 'The file size must not exceed 10MB.',
        ]);

        // Validate academic relationships
        if ($request->filled('section_id')) {
            $section = Section::where('id', $request->section_id)->where('my_class_id', $request->my_class_id)->first();
            if (!$section) {
                if ($request->expectsJson()) {
                    return response()->json(['msg' => __('The selected section does not belong to the selected class.')], 422);
                }
                return back()->withInput()->with('flash_danger', __('The selected section does not belong to the selected class.'));
            }
        }

        if ($request->filled('subject_id')) {
            $subject = Subject::where('id', $request->subject_id)->where('my_class_id', $request->my_class_id)->first();
            if (!$subject) {
                if ($request->expectsJson()) {
                    return response()->json(['msg' => __('The selected subject does not belong to the selected class.')], 422);
                }
                return back()->withInput()->with('flash_danger', __('The selected subject does not belong to the selected class.'));
            }
        }

        // If teacher, strictly verify assignment to exact selected subject and/or class/section
        if ($user->user_type === 'teacher') {
            if ($request->filled('subject_id')) {
                // When subject is selected, teacher must be assigned to that exact subject in that class
                $teachesSubject = Subject::where('id', $request->subject_id)
                    ->where('my_class_id', $request->my_class_id)
                    ->where('teacher_id', $user->id)
                    ->exists();

                if (!$teachesSubject) {
                    if ($request->expectsJson()) {
                        return response()->json(['msg' => __('You are not assigned to teach the selected subject in this class.')], 422);
                    }
                    return back()->withInput()->with('flash_danger', __('You are not assigned to teach the selected subject in this class.'));
                }
            } else {
                // When no subject is selected, teacher must teach a subject or section in this class
                $teachesClass = Section::where('my_class_id', $request->my_class_id)->where('teacher_id', $user->id)->exists()
                    || Subject::where('my_class_id', $request->my_class_id)->where('teacher_id', $user->id)->exists();

                if (!$teachesClass) {
                    if ($request->expectsJson()) {
                        return response()->json(['msg' => __('You are not assigned to this class.')], 422);
                    }
                    return back()->withInput()->with('flash_danger', __('You are not assigned to this class.'));
                }
            }

            if ($request->filled('section_id')) {
                $isSectionTeacher = Section::where('id', $request->section_id)->where('teacher_id', $user->id)->exists();
                $teachesSubjectInClass = $request->filled('subject_id')
                    ? Subject::where('id', $request->subject_id)->where('teacher_id', $user->id)->exists()
                    : Subject::where('my_class_id', $request->my_class_id)->where('teacher_id', $user->id)->exists();

                if (!$isSectionTeacher && !$teachesSubjectInClass) {
                    if ($request->expectsJson()) {
                        return response()->json(['msg' => __('You are not assigned to this section.')], 422);
                    }
                    return back()->withInput()->with('flash_danger', __('You are not assigned to this section.'));
                }
            }
        }

        $file = $request->file('material_file');
        $originalName = basename($file->getClientOriginalName());
        $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName);
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        if (empty($safeOriginalName) || $safeOriginalName === '.' || $safeOriginalName === '..') {
            $safeOriginalName = 'study_material_' . time() . '.' . $extension;
        }

        // Extra check against dangerous extensions
        $dangerousExtensions = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'js', 'vbs', 'ps1', 'cgi', 'pl', 'py', 'htm', 'html'];
        if (in_array(strtolower($extension), $dangerousExtensions, true)) {
            if ($request->expectsJson()) {
                return response()->json(['msg' => __('Executable and script files are strictly prohibited.')], 422);
            }
            return back()->withInput()->with('flash_danger', __('Executable and script files are strictly prohibited.'));
        }

        // Store privately on local disk
        $storedPath = $file->store('study_materials', 'local');

        try {
            StudyMaterial::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'file_path' => $storedPath,
                'file_name' => $safeOriginalName,
                'file_size' => $fileSize,
                'file_type' => strtolower($extension),
                'my_class_id' => $validated['my_class_id'],
                'section_id' => $validated['section_id'] ?? null,
                'subject_id' => $validated['subject_id'] ?? null,
                'teacher_id' => $user->id,
            ]);
        } catch (\Throwable $e) {
            if (Storage::disk('local')->exists($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }
            if ($request->expectsJson()) {
                return response()->json(['msg' => __('An error occurred while saving the study material. Please try again.')], 500);
            }
            return back()->withInput()->with('flash_danger', __('An error occurred while saving the study material. Please try again.'));
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'msg' => __('Study material uploaded successfully.')]);
        }

        return redirect()->route('study_materials.index')->with('flash_success', __('Study material uploaded successfully.'));
    }

    public function download($id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $material = StudyMaterial::visibleTo($user)->find($id);
        if (!$material) {
            abort(403, __('You are not authorized to download this study material.'));
        }

        $disk = Storage::disk('local');
        $filePath = $material->file_path;

        if ($disk->exists($filePath)) {
            return $disk->download($filePath, $material->file_name);
        }

        // Fallback for legacy files
        if (Storage::exists($filePath)) {
            return Storage::download($filePath, $material->file_name);
        }

        return back()->with('flash_danger', __('File not found on storage server.'));
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $material = StudyMaterial::findOrFail($id);

        // Allowed only for Super Admin, Admin, or the uploading teacher
        if (!Qs::userIsTeamSA() && $material->teacher_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        \Illuminate\Support\Facades\DB::transaction(function() use ($material) {
            $disk = Storage::disk('local');
            if ($disk->exists($material->file_path)) {
                $disk->delete($material->file_path);
            } elseif (Storage::exists($material->file_path)) {
                Storage::delete($material->file_path);
            }
            $material->delete();
        });

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'msg' => __('Study material deleted successfully.')]);
        }

        return redirect()->route('study_materials.index')->with('flash_success', __('Study material deleted successfully.'));
    }
}
