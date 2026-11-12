<?php

namespace App\Http\View\Composers;

use App\Helpers\Qs;
use App\Models\MyClass;
use App\Models\Notice;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ShellComposer
{
    /**
     * Request-scoped cache keyed by user ID so shared partials never query twice
     * and never leak user-specific counts across sessions/tests.
     *
     * @var array<int, array<string, mixed>>
     */
    protected static array $requestCache = [];

    public function compose(View $view): void
    {
        $user = Auth::user();
        if (!$user) {
            $view->with([
                'shellNoticeCount' => 0,
                'shellClasses' => collect(),
            ]);
            return;
        }

        $cacheKey = spl_object_id(request()) . ':' . $user->id;

        if (!isset(self::$requestCache[$cacheKey])) {
            $noticeCount = 0;
            $classes = collect();

            try {
                $noticeCount = Notice::visibleTo($user)
                    ->where('is_published', true)
                    ->count();
            } catch (\Throwable $e) {
                $noticeCount = 0;
            }

            if (Qs::userIsTeamSAT()) {
                try {
                    $classes = MyClass::orderBy('name')->get(['id', 'name']);
                } catch (\Throwable $e) {
                    $classes = collect();
                }
            }

            // Keep static cache bounded to the current request only
            self::$requestCache = [
                $cacheKey => [
                    'shellNoticeCount' => $noticeCount,
                    'shellClasses' => $classes,
                ],
            ];
        }

        $view->with(self::$requestCache[$cacheKey]);
    }
}

