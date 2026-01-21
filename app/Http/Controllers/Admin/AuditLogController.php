<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // V57-HIGH-01 FIX: Add authorization for audit log viewing
        $this->authorize('audit.view');
        
        $per = min(max((int) $request->integer('per_page', 20), 1), 100);
        $q = DB::table('audit_logs')->orderByDesc('id');
        if ($request->filled('user_id')) {
            $q->where('user_id', $request->integer('user_id'));
        }
        if ($request->filled('action')) {
            $q->where('action', 'like', '%'.$request->input('action').'%');
        }
        if ($request->filled('subject_type')) {
            $q->where('subject_type', $request->input('subject_type'));
        }
        if ($request->filled('from')) {
            $q->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('created_at', '<=', $request->input('to'));
        }

        return $this->ok($q->paginate($per));
    }

    public function show(int $id)
    {
        // V57-HIGH-01 FIX: Add authorization for audit log viewing
        $this->authorize('audit.view');
        
        $row = DB::table('audit_logs')->where('id', $id)->first();
        abort_unless($row, 404);

        return $this->ok($row);
    }
}
