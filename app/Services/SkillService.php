<?php

namespace App\Services;

use App\Models\UserSkill;

class SkillService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function index ()
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
        return ['data'=>$skill,'message'=>'Skill retrieved successfully','code'=>200];
    }
    public function store($request)
    {
        $user=auth()->user();
        $user_id=$user->id;
        $name=$request['name'];
        $type=$request['type'];

        $skill=UserSkill::create([
            'user_id'=>$user_id,
            'name'=>$name,
            'type'=>$type,
        ]);

        $message='Skill created successfully';
        $code=200;
        return ['data'=>$skill,'message'=>$message,'code'=>$code];

    }
    public function update($request,$id)
    {
       $user=auth()->user();
       $skill=$user->userSkills()->find($id);

       if(!$skill)
       {
           $message='Skill not found';
           $code=404;
           return ['data'=>$skill,'message'=>$message,'code'=>$code];
       }

       $skill->name=$request['name']??$skill->name;
       $skill->type=$request['type']??$skill->type;
       $skill->save();

       $message='Skill updated successfully';
       $code=200;
       return ['data'=>$skill,'message'=>$message,'code'=>$code];
    }
    public function destroy($id)
    {
        $user=auth()->user();
        $skill=$user->userSkills()->find($id);
        if(!$skill)
        {
            $message='Skill not found';
            $code=404;
            return ['data'=>$skill,'message'=>$message,'code'=>$code];
        }
        $skill->delete();
        $message='Skill deleted successfully';
        $code=200;
        return ['data'=>$skill,'message'=>$message,'code'=>$code];
    }
}
