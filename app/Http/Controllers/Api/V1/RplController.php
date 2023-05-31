<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Rpl;
use App\Models\Season;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RplController extends Controller
{
    /**
     * For test
     *
     * @return \Illuminate\Http\Response
     */
    public function generate()
    {
        return response()->json(
            Rpl::factory(1)->create()
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rpls = Rpl::query()
            ->with('airline')
            ->with('status');

        return response()->json(
            $rpls->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        dd(__METHOD__);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Rpl  $rpl
     * @return \Illuminate\Http\Response
     */
    public function show(Rpl $rpl)
    {
        dd(__METHOD__);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Rpl  $rpl
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Rpl $rpl)
    {
        dd(__METHOD__);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Rpl  $rpl
     * @return \Illuminate\Http\Response
     */
    public function destroy(Rpl $rpl)
    {
        dd(__METHOD__);
    }

    public function automaticProcessing(Request $request)
    {
        $period_begin = $request->has('period_begin')
            ? Carbon::createFromFormat('Y.m.d', $request->input('period_begin'))
            : Carbon::now();

        $period_end = Carbon::parse(
            Season::query()
                ->where(function ($query) use ($period_begin) {
                    $query
                        ->whereDate('begin_date', '<=', $period_begin)
                        ->whereDate('end_date', '>=', $period_begin);
                })
                ->first()
                ->end_date
        );

        /* RPL за период */
        $rplsPeriod = Rpl::query()
            ->where(function ($query) use ($period_begin, $period_end) {
                $query
                    ->whereBetween('FROMRPL', [$period_begin, $period_end])
                    ->whereBetween('UNTILRPL', [$period_begin, $period_end]);
            });

        /* RPL за месяц */
        $rplsMonth = $rplsPeriod
            ->get()
            ->groupBy(function($query) {
                return Carbon::parse($query->FROMRPL)->month;
            });

        foreach ($rplsMonth as $rplMonth) {
            dump(
                $rplMonth
            );
        }

//        foreach ($rpls->cursor() as $rpl) {
//            dump(
//                $rpl->DEP_DAYS,
//                strlen(str_replace( ['0'], '', $rpl->DEP_DAYS)),
//            );
//        }

//        return response()->json(
//            $rpls->get()
//        );
    }

    public  function manualProcessing(Request $request)
    {
        dd(__METHOD__, $request->all());
    }
}
