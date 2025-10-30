<?php

namespace Modules\KnowledgeBaseAPI\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class KnowledgeBaseAPIController extends Controller
{
    /**
     * Health check endpoint
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'module' => 'KnowledgeBaseAPI',
            'version' => '1.0.0'
        ]);
    }

    /**
     * List all knowledge base categories
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listCategories(Request $request): JsonResponse
    {
        try {
            $categories = DB::table('kb_categories')
                ->select('id', 'name', 'description', 'slug', 'parent_id', 'order', 'created_at', 'updated_at')
                ->orderBy('order', 'asc')
                ->orderBy('name', 'asc')
                ->get();

            // Build hierarchical structure
            $categoriesTree = $this->buildCategoryTree($categories);

            return response()->json([
                'success' => true,
                'data' => $categoriesTree,
                'count' => $categories->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch categories',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific category by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getCategory(int $id): JsonResponse
    {
        try {
            $category = DB::table('kb_categories')
                ->where('id', $id)
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'error' => 'Category not found'
                ], 404);
            }

            // Get articles in this category
            $articles = DB::table('kb_articles')
                ->where('category_id', $id)
                ->where('status', 'published')
                ->select('id', 'title', 'slug', 'excerpt', 'views', 'created_at', 'updated_at')
                ->orderBy('order', 'asc')
                ->orderBy('title', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => $category,
                    'articles' => $articles,
                    'article_count' => $articles->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch category',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all knowledge base articles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listArticles(Request $request): JsonResponse
    {
        try {
            $query = DB::table('kb_articles')
                ->where('status', 'published')
                ->select('id', 'category_id', 'title', 'slug', 'excerpt', 'views', 'created_at', 'updated_at');

            // Filter by category if provided
            if ($request->has('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }

            // Pagination
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            
            $articles = $query
                ->orderBy('order', 'asc')
                ->orderBy('title', 'asc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            $total = DB::table('kb_articles')
                ->where('status', 'published')
                ->when($request->has('category_id'), function($q) use ($request) {
                    return $q->where('category_id', $request->get('category_id'));
                })
                ->count();

            // Include category information
            foreach ($articles as $article) {
                $category = DB::table('kb_categories')
                    ->where('id', $article->category_id)
                    ->first();
                $article->category = $category;
            }

            return response()->json([
                'success' => true,
                'data' => $articles,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch articles',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific article by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getArticle(int $id): JsonResponse
    {
        try {
            $article = DB::table('kb_articles')
                ->where('id', $id)
                ->where('status', 'published')
                ->first();

            if (!$article) {
                return response()->json([
                    'success' => false,
                    'error' => 'Article not found'
                ], 404);
            }

            // Get category information
            $category = DB::table('kb_categories')
                ->where('id', $article->category_id)
                ->first();

            // Increment view count
            DB::table('kb_articles')
                ->where('id', $id)
                ->increment('views');

            return response()->json([
                'success' => true,
                'data' => [
                    'article' => $article,
                    'category' => $category
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch article',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search knowledge base articles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            
            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Search query is required'
                ], 400);
            }

            $articles = DB::table('kb_articles')
                ->where('status', 'published')
                ->where(function($q) use ($query) {
                    $q->where('title', 'like', '%' . $query . '%')
                      ->orWhere('content', 'like', '%' . $query . '%')
                      ->orWhere('excerpt', 'like', '%' . $query . '%');
                })
                ->select('id', 'category_id', 'title', 'slug', 'excerpt', 'views', 'created_at', 'updated_at')
                ->orderBy('views', 'desc')
                ->orderBy('title', 'asc')
                ->limit(50)
                ->get();

            // Include category information
            foreach ($articles as $article) {
                $category = DB::table('kb_categories')
                    ->where('id', $article->category_id)
                    ->first();
                $article->category = $category;
            }

            return response()->json([
                'success' => true,
                'query' => $query,
                'data' => $articles,
                'count' => $articles->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List public categories (no authentication required)
     *
     * @return JsonResponse
     */
    public function listPublicCategories(): JsonResponse
    {
        try {
            $categories = DB::table('kb_categories')
                ->where('visibility', 'public')
                ->select('id', 'name', 'description', 'slug', 'parent_id', 'order')
                ->orderBy('order', 'asc')
                ->orderBy('name', 'asc')
                ->get();

            $categoriesTree = $this->buildCategoryTree($categories);

            return response()->json([
                'success' => true,
                'data' => $categoriesTree,
                'count' => $categories->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch public categories',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public category
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getPublicCategory(int $id): JsonResponse
    {
        try {
            $category = DB::table('kb_categories')
                ->where('id', $id)
                ->where('visibility', 'public')
                ->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'error' => 'Public category not found'
                ], 404);
            }

            $articles = DB::table('kb_articles')
                ->where('category_id', $id)
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->select('id', 'title', 'slug', 'excerpt', 'views')
                ->orderBy('order', 'asc')
                ->orderBy('title', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => $category,
                    'articles' => $articles,
                    'article_count' => $articles->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch public category',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List public articles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listPublicArticles(Request $request): JsonResponse
    {
        try {
            $query = DB::table('kb_articles')
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->select('id', 'category_id', 'title', 'slug', 'excerpt', 'views');

            if ($request->has('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }

            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            
            $articles = $query
                ->orderBy('order', 'asc')
                ->orderBy('title', 'asc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            foreach ($articles as $article) {
                $category = DB::table('kb_categories')
                    ->where('id', $article->category_id)
                    ->where('visibility', 'public')
                    ->first();
                $article->category = $category;
            }

            $total = DB::table('kb_articles')
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->when($request->has('category_id'), function($q) use ($request) {
                    return $q->where('category_id', $request->get('category_id'));
                })
                ->count();

            return response()->json([
                'success' => true,
                'data' => $articles,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch public articles',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public article
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getPublicArticle(int $id): JsonResponse
    {
        try {
            $article = DB::table('kb_articles')
                ->where('id', $id)
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->first();

            if (!$article) {
                return response()->json([
                    'success' => false,
                    'error' => 'Public article not found'
                ], 404);
            }

            $category = DB::table('kb_categories')
                ->where('id', $article->category_id)
                ->where('visibility', 'public')
                ->first();

            DB::table('kb_articles')
                ->where('id', $id)
                ->increment('views');

            return response()->json([
                'success' => true,
                'data' => [
                    'article' => $article,
                    'category' => $category
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch public article',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publicSearch(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            
            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Search query is required'
                ], 400);
            }

            $articles = DB::table('kb_articles')
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->where(function($q) use ($query) {
                    $q->where('title', 'like', '%' . $query . '%')
                      ->orWhere('content', 'like', '%' . $query . '%')
                      ->orWhere('excerpt', 'like', '%' . $query . '%');
                })
                ->select('id', 'category_id', 'title', 'slug', 'excerpt', 'views')
                ->orderBy('views', 'desc')
                ->orderBy('title', 'asc')
                ->limit(50)
                ->get();

            foreach ($articles as $article) {
                $category = DB::table('kb_categories')
                    ->where('id', $article->category_id)
                    ->where('visibility', 'public')
                    ->first();
                $article->category = $category;
            }

            return response()->json([
                'success' => true,
                'query' => $query,
                'data' => $articles,
                'count' => $articles->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Public search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build hierarchical category tree
     *
     * @param $categories
     * @param int|null $parentId
     * @return array
     */
    private function buildCategoryTree($categories, ?int $parentId = null): array
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $categoryArray = (array) $category;
                $categoryArray['children'] = $this->buildCategoryTree($categories, $category->id);
                $tree[] = $categoryArray;
            }
        }
        
        return $tree;
    }
}
