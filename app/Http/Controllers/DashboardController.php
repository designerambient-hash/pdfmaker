<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the modern dashboard with documents, filtering, search, and sorting.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $tab = $request->query('tab', 'all'); // all, recent, starred, trash
        $search = $request->query('search', '');
        $sort = $request->query('sort', 'updated_at'); // updated_at, created_at, title
        $order = $request->query('order', 'desc'); // desc, asc
        $view = $request->query('view', 'grid'); // grid, list

        // Base query strictly scoped to authenticated user
        $query = Document::where('user_id', $userId);

        // Tab filters
        if ($tab === 'trash') {
            $query->where('status', 'trash');
        } else {
            $query->where('status', 'active');

            if ($tab === 'starred') {
                $query->where('is_starred', true);
            } elseif ($tab === 'recent') {
                $query->where('updated_at', '>=', now()->subDays(7));
            }
        }

        // Search query
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $allowedSorts = ['title', 'created_at', 'updated_at'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'updated_at';
        }
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $order);

        $documents = $query->withCount('pages')->paginate(12)->withQueryString();

        // Statistics counts strictly for the authenticated user
        $baseCounts = Document::where('user_id', $userId);

        $totalActive = (clone $baseCounts)->where('status', 'active')->count();
        $totalStarred = (clone $baseCounts)->where('status', 'active')->where('is_starred', true)->count();
        $totalTrash = (clone $baseCounts)->where('status', 'trash')->count();

        // Featured templates
        $templates = Template::where('is_system', true)->limit(6)->get();

        return view('dashboard.index', compact(
            'documents',
            'tab',
            'search',
            'sort',
            'order',
            'view',
            'totalActive',
            'totalStarred',
            'totalTrash',
            'templates'
        ));
    }
}

