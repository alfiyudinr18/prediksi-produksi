<?php

namespace App\Http\Controllers;

use App\Models\Produksi;

class TrainingController extends Controller
{

    public function index()
    {
        $evaluation = null;

        $path = base_path('python/models/evaluation.json');

        if(file_exists($path)){

            $evaluation = json_decode(

                file_get_contents($path),

                true

            );

        }

        return view(
            'training.index',
            compact('evaluation')
        );
    }

    public function train()
    {

        if(Produksi::count()==0){

            return back()->with(

                'error',

                'Dataset belum tersedia.'

            );

        }

        $python="python";

        $script=base_path(

            'python/scripts/train_model.py'

        );

        $output=shell_exec(

            $python." ".$script

        );

        return back()->with(

            'success',

            $output

        );

    }

}
