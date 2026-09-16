<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\KnowledgeArticle;
use App\Services\KnowledgeArticleService;
use App\Services\UserPolicy;
use RuntimeException;

final class KnowledgeBaseController extends Controller
{
    public function index(Request $request): void
    {
        $currentUser = $this->currentUser();
        $filters = $this->filters($request->query());
        $page = max(1, (int) ($request->input('page', 1)));

        $service = $this->service();
        $listing = $service->listPublished($filters, $page, 10);

        $this->app->view()->render('knowledge_base/index', [
            'pageTitle' => 'Knowledge Base',
            'activeNavigation' => 'knowledge-base',
            'currentUser' => $currentUser,
            'csrfToken' => $this->app->csrf()->token(),
            'filters' => $filters,
            'categories' => $service->categories(),
            'listing' => $listing,
            'successMessage' => $this->app->session()->consumeFlash('success'),
            'errorMessage' => $this->app->session()->consumeFlash('error'),
        ]);
    }

    public function show(Request $request): void
    {
        $currentUser = $this->currentUser();
        $identifier = (string) ($request->route('id', $request->input('id')) ?? '');
        $article = $this->service()->findPublished($identifier);

        if ($article === null) {
            throw new HttpException(404, 'Knowledge article not found.');
        }

        $this->service()->incrementViews((int) $article['id']);
        $article['view_count'] = (int) ($article['view_count'] ?? 0) + 1;

        $this->app->view()->render('knowledge_base/show', [
            'pageTitle' => (string) $article['title'],
            'activeNavigation' => 'knowledge-base',
            'currentUser' => $currentUser,
            'csrfToken' => $this->app->csrf()->token(),
            'article' => $article,
        ]);
    }

    public function create(Request $request): void
    {
        $this->ensureAdmin();
        $this->renderForm('Create article', '/knowledge-base', [], []);
    }

    public function store(Request $request): never
    {
        $this->ensureAdmin();
        $this->ensureCsrf($request);

        $input = $request->post();
        $errors = $this->service()->validate($input);

        if ($errors !== []) {
            $this->renderForm('Create article', '/knowledge-base', $input, $errors);
            exit;
        }

        try {
            $article = $this->service()->create($input, $this->currentUser()['id']);
            $this->app->session()->flash('success', 'Knowledge article created successfully.');
            Response::redirect('/knowledge-base/' . rawurlencode((string) $article));
        } catch (RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
            $this->renderForm('Create article', '/knowledge-base', $input, []);
            exit;
        }
    }

    public function edit(Request $request): void
    {
        $this->ensureAdmin();
        $articleId = $this->findArticleId($request);
        $article = $this->service()->findForAdmin($articleId);

        if ($article === null) {
            throw new HttpException(404, 'Knowledge article not found.');
        }

        $this->renderForm('Edit article', '/knowledge-base/' . $articleId, $article, []);
    }

    public function update(Request $request): never
    {
        $this->ensureAdmin();
        $this->ensureCsrf($request);

        $articleId = $this->findArticleId($request);
        $input = $request->post();
        $input['id'] = $articleId;

        $errors = $this->service()->validate($input);
        if ($errors !== []) {
            $this->renderForm('Edit article', '/knowledge-base/' . $articleId, $input, $errors);
            exit;
        }

        try {
            $this->service()->update($articleId, $input, $this->currentUser()['id']);
            $this->app->session()->flash('success', 'Knowledge article updated successfully.');
            Response::redirect('/knowledge-base/' . $articleId);
        } catch (RuntimeException $exception) {
            $this->app->session()->flash('error', $exception->getMessage());
            $this->renderForm('Edit article', '/knowledge-base/' . $articleId, $input, []);
            exit;
        }
    }

    /** @return array<string, mixed> */
    private function filters(array $input): array
    {
        $search = trim((string) ($input['search'] ?? ''));
        $category = trim((string) ($input['category'] ?? ''));

        return [
            'search' => $search,
            'category' => $category,
        ];
    }

    private function service(): KnowledgeArticleService
    {
        return new KnowledgeArticleService(new KnowledgeArticle($this->app->database()));
    }

    private function ensureAdmin(): void
    {
        $currentUser = $this->currentUser();
        if (!(new UserPolicy())->canManage($currentUser)) {
            throw new HttpException(403, 'You do not have permission to manage knowledge base articles.');
        }
    }

    /** @param array<string, mixed> $values @param array<string, string> $errors */
    private function renderForm(string $title, string $action, array $values, array $errors): void
    {
        $this->app->view()->render('knowledge_base/form', [
            'pageTitle' => $title,
            'activeNavigation' => 'knowledge-base',
            'currentUser' => $this->currentUser(),
            'csrfToken' => $this->app->csrf()->token(),
            'action' => $action,
            'values' => $values,
            'errors' => $errors,
            'editing' => str_contains($action, '/edit') || isset($values['id']),
            'successMessage' => $this->app->session()->consumeFlash('success'),
            'errorMessage' => $this->app->session()->consumeFlash('error'),
        ]);
    }

    private function findArticleId(Request $request): int
    {
        $articleId = filter_var($request->route('id', $request->input('id')), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($articleId === false || $articleId === null) {
            throw new HttpException(404, 'Knowledge article not found.');
        }

        return (int) $articleId;
    }

    private function ensureCsrf(Request $request): void
    {
        try {
            $this->app->csrf()->validate((string) $request->input('_token'));
        } catch (RuntimeException) {
            throw new HttpException(419, 'Your session has expired. Please try again.');
        }
    }
}
