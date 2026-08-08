<?php

namespace App\Services;

class SkillService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function index()
    {
        $user=auth()->user();
        $skills=$user->userSkills()->with('user.profile')->latest()->get();

        $message='Skills retrieved successfully';
        $code=200;
        return ['data'=>$skills,'message'=>$message,'code'=>$code];
    }
    public function show($id)
    {
        $user=auth()->user();
        $skill=$user->userSkills()->with('user.profile')->find($id);
        if(!$skill)
        {
            $message='Skill not found';
            $code=404;
            return ['data'=>$skill,'message'=>$message,'code'=>$code];
        }
        return ['data'=>$skill,'message'=>'Skill retrieved successfully'];
    }
    public function store($request)
    {
        $user=auth()->user();
    }
    public function update($request,$id)
    {

    }
    public function destroy($id)
    {

    }
}
