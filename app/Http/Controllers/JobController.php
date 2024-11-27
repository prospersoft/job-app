<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Group jobs by 'featured' (0 or 1)
        $jobs = Job::latest()->with(['employer', 'tags'])->get()->groupBy('featured');

        // Check if the groups exist, otherwise default to empty collections
        $normalJobs = $jobs->has(0) ? $jobs[0] : collect();
        $featuredJobs = $jobs->has(1) ? $jobs[1] : collect();

        return view('jobs.index', [
            'jobs' => $normalJobs,
            'featuredJobs' => $featuredJobs,
            'tags' => Tag::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('jobs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'title' => ['required'],
            'salary' => ['required'],
            'location' => ['required'],
            'schedule' => ['required', Rule::in(['Part Time', 'Full Time'])],
            'url' => ['required', 'active_url'],
            'tags' => ['nullable'],
        ]);

        $attributes['featured'] = $request->has('featured');

        // Get the authenticated user
        $user = Auth::user();

        // Check if the authenticated user has an employer
        if (!$user->employer) {
            return redirect()->back()->withErrors(['employer' => 'You must create an employer profile before posting a job.']);
        }

        // Create a new job for the authenticated employer
        $job = $user->employer->jobs()->create(Arr::except($attributes, 'tags'));

        // Handle tags if provided
        if ($attributes['tags'] ?? false) {
            foreach (explode(',', $attributes['tags']) as $tag) {
                $job->tag($tag);  // Assuming a `tag()` method exists on the Job model
            }
        }

        return redirect('/'); // You might want to redirect to a specific page like job listings
    }

}
