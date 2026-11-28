<?php

namespace App\Services;

use App\Helpers\NepaliHelper;
use App\Helpers\Qs;
use App\Models\StudentRecord;
use App\User;
use Illuminate\Http\UploadedFile;

class ErpSupportService
{
    /**
     * Resolve paired AD and BS dates from user inputs with optional fallback to today.
     *
     * @return array{ad: ?string, bs: ?string}
     */
    public function resolveDualDates(?string $adDate, ?string $bsDate, bool $defaultToday = true): array
    {
        $ad = $adDate ? trim($adDate) : null;
        $bs = $bsDate ? trim($bsDate) : null;

        if ($bs && !$ad) {
            $ad = NepaliHelper::bsToAd($bs);
        }

        if ($ad && !$bs) {
            $bs = NepaliHelper::adToBs($ad);
        }

        if (!$ad && $defaultToday) {
            $ad = now()->toDateString();
            $bs = $bs ?: NepaliHelper::adToBs($ad);
        }

        return ['ad' => $ad, 'bs' => $bs];
    }

    /**
     * Return student_record IDs visible to a user, or null for unrestricted staff roles.
     *
     * @return array<int>|null
     */
    public function accessibleStudentRecordIds(User $user): ?array
    {
        if (Qs::userIsStaff()) {
            return null;
        }

        if ($user->user_type === 'student') {
            return StudentRecord::where('user_id', $user->id)->pluck('id')->all();
        }

        if ($user->user_type === 'parent') {
            return StudentRecord::where('my_parent_id', $user->id)->pluck('id')->all();
        }

        return [];
    }

    /**
     * Check whether the given user can view a specific student record.
     */
    public function canAccessStudentRecord(User $user, int $studentRecordId): bool
    {
        $allowed = $this->accessibleStudentRecordIds($user);
        if ($allowed === null) {
            return true;
        }

        return in_array($studentRecordId, $allowed, true);
    }

    /**
     * Store a private uploaded attachment safely on the local disk.
     *
     * @return array{path: string, name: string}
     */
    public function storePrivateUpload(UploadedFile $file, string $directory): array
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $dangerous = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'js', 'vbs', 'ps1', 'cgi', 'pl', 'py', 'htm', 'html'];
        if (in_array($ext, $dangerous, true)) {
            abort(422, __('Executable and script files are strictly prohibited.'));
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($file->getClientOriginalName()));
        if (!$safeName || $safeName === '.' || $safeName === '..') {
            $safeName = $directory . '_' . time() . '.' . $ext;
        }

        return [
            'path' => $file->store($directory, 'local'),
            'name' => $safeName,
        ];
    }

    /**
     * Record an audit event through AuditLogService without failing primary workflows.
     */
    public function audit(string $action, string $category, string $description, $auditable = null, array $newValues = []): void
    {
        app(AuditLogService::class)->log($action, $category, $description, $auditable, [], $newValues, 'success');
    }
}
