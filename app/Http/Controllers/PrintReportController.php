<?php

namespace App\Http\Controllers;

use App\Services\Reports\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PrintReportController extends Controller
{
    public function __construct(
        protected ReportService $reports,
    ) {}

    public function monthlyIssuance(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $html = $this->reports->renderMonthlyIssuanceHtml(
            Carbon::createFromFormat('Y-m', $month)
        );

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function complianceExport(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to', now());

        $html = $this->reports->renderComplianceBundleHtml($from, $to);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function departmentRoster(Request $request)
    {
        $departmentId = $request->query('department_id');

        $html = $this->reports->renderDepartmentRosterHtml($departmentId);

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'inline')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
