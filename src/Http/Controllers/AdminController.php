<?php

namespace TypiCMS\Modules\Pages\Http\Controllers;

use TypiCMS\Modules\Core\Http\Controllers\BaseAdminController;
use TypiCMS\Modules\Pages\Http\Requests\FormRequest;
use TypiCMS\Modules\Pages\Models\Page;
use TypiCMS\Modules\Pages\Repositories\EloquentPage;

class AdminController extends BaseAdminController
{
    public function __construct(EloquentPage $page)
    {
        parent::__construct($page);
    }

    /**
     * List models.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $models = $this->repository->allNested([
            'id',
            'parent_id',
            'title',
            'position',
            'status',
            'private',
            'redirect',
            'module',
            'slug',
            'uri',
        ]);
        app('JavaScript')->put('models', $models);

        return view('pages::admin.index');
    }

    /**
     * Create form for a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $model = $this->repository->createModel();

        return view('pages::admin.create')
            ->with(compact('model'));
    }

    /**
     * Edit form for the specified resource.
     *
     * @param \TypiCMS\Modules\Pages\Models\Page $page
     *
     * @return \Illuminate\View\View
     */
    public function edit(Page $page)
    {
        return view('pages::admin.edit')
            ->with(['model' => $page]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \TypiCMS\Modules\Pages\Http\Requests\FormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(FormRequest $request)
    {
        $data = $request->all();
        $data['parent_id'] = null;
        $page = $this->repository->create($data);

        return $this->redirect($request, $page);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \TypiCMS\Modules\Pages\Models\Page               $page
     * @param \TypiCMS\Modules\Pages\Http\Requests\FormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Page $page, FormRequest $request)
    {
        $data = $request->all();

        if ($request->wantsJson()) {
            return $this->ajaxUpdate($page->id, $data);
        }

        $data['parent_id'] = $data['parent_id'] ?: null;

        $this->repository->update($page->id, $data);
        $this->repository->syncRelation($page, $data, 'galleries');

        event('page.resetChildrenUri', [$page]);

        return $this->redirect($request, $page);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int   $id
     * @param array $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function ajaxUpdate($id, array $data)
    {
        $updated = $this->repository->update($id, $data);

        return response()->json([
            'error' => !$updated,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \TypiCMS\Modules\Pages\Models\Page $page
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Page $page)
    {
        $deleted = $this->repository->delete($page);

        return response()->json([
            'error' => !$deleted,
        ]);
    }
}
