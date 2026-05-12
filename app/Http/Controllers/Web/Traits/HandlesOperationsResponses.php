<?php

namespace App\Http\Controllers\Web\Traits;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait HandlesOperationsResponses
{
    protected function moduleResponse(
        Request $request,
        string $pageView,
        array $viewData,
        string $tableView,
        ?string $modalView = null,
        array $extra = [],
    ): View|JsonResponse {
        if ($request->ajax()) {
            $shouldRenderModal = $modalView
                && ($request->boolean('include_modal') || in_array((string) $request->query('modal'), ['create', 'edit', 'detail'], true));

            return response()->json([
                'tableHtml' => view($tableView, $viewData)->render(),
                'modalHtml' => $shouldRenderModal ? view($modalView, $viewData)->render() : '',
                ...$extra,
            ]);
        }

        return view($pageView, $viewData);
    }

    protected function actionResponse(Request $request, string $route, string $message, array $params = []): RedirectResponse|JsonResponse
    {
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->route($route, $params)->with('status', $message);
    }

    protected function actionErrorResponse(
        Request $request,
        string $route,
        string $key,
        string $message,
        int $status = 422,
        array $params = []
    ): RedirectResponse|JsonResponse {
        if ($request->ajax()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    $key => [$message],
                ],
            ], $status);
        }

        return redirect()->route($route, $params)->withErrors([$key => $message]);
    }

    protected function perPage(Request $request, int $default = 20, int $max = 100): int
    {
        return min(max(1, $request->integer('per_page', $default)), $max);
    }
}
