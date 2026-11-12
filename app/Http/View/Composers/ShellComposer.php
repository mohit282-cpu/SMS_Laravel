<?php

namespace App\Http\View\Composers;

use App\Helpers\Qs;
use App\Models\MyClass;
use App\Models\Notice;
use App\Models\StudentRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ShellComposer
{
    /**
     * Request-scoped cache keyed by request instance and user ID so shared
     * partials never query twice and never leak across sessions/tests.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $requestCache = [];

    public function compose(View $view): void
    {
        $user = Auth::user();
        if (!$user) {
            $view->with([
                'shellNoticeCount' => 0,
                'shellClasses' => collect(),
                'shellStudentRecord' => null,
            ]);
            return;
        }

        $cacheKey = spl_object_id(request()) . ':' . $user->id;

        if (!isset(self::$requestCache[$cacheKey])) {
            $noticeCount = 0;
            $classes = collect();
            $studentRecord = null;

            try {
                $noticeCount = Notice::visibleTo($user)
                    ->where('is_published', true)
                    ->count();
            } catch (\Throwable $e) {
                $this->logQueryFailure('notice count', $user->id, $e);
                $noticeCount = 0;
            }

            if (Qs::userIsTeamSAT()) {
                try {
                    $classes = MyClass::orderBy('name')->get(['id', 'name']);
                } catch (\Throwable $e) {
                    $this->logQueryFailure('class list', $user->id, $e);
                    $classes = collect();
                }
            }

            if (Qs::userIsStudent()) {
                try {
                    $studentRecord = StudentRecord::where('user_id', $user->id)->first();
                } catch (\Throwable $e) {
                    $this->logQueryFailure('student record', $user->id, $e);
                    $studentRecord = null;
                }
            }

            // Keep static cache bounded to the current request only
            self::$requestCache = [
                $cacheKey => [
                    'shellNoticeCount' => $noticeCount,
                    'shellClasses' => $classes,
                    'shellStudentRecord' => $studentRecord,
                ],
            ];
        }

        $view->with(self::$requestCache[$cacheKey]);
    }

    protected function logQueryFailure(string $context, int $userId, \Throwable $e): void
    {
        $msg = $e->getMessage();
        if ($e->getCode() === '42S02' || str_contains($msg, 'Base table or view not found') || str_contains($msg, "doesn't exist")) {
            return;
        }

        Log::warning("ShellComposer failed to load {$context}", [
            'user_id' => $userId,
            'code' => $e->getCode(),
            'error' => $msg,
        ]);
    }
}
