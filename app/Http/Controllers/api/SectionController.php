<?php

namespace App\Http\Controllers\api;

use App\Section;
use App\Category;
use App\User;
use App\Poll;
use App\Http\Controllers\ApiResponseController;
use App\Http\Controllers\Controller\api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SectionController extends ApiResponseController
{
    public function __construct(Request $request)
    {
        $this->user = User::from('users as c')
                        ->selectRaw('c.*, members.rol_id as rol_id, members.alliance_id as alliance_id')
                        ->leftJoin('members', 'members.user_id', '=', 'c.rut')
                        ->where('api_token', $request->api_token)
                        ->first();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sections = Section::orderBy('position', 'ASC')
                        ->paginate(10);
        
        return $this->successResponse($sections);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function home(Request $request)
    {
        $sections = Section::where('status', 1)
                        ->orderBy('position', 'ASC')
                        ->get();
        
        return $this->successResponse($sections);
    }

    /**
     * Store the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->icon_type_id == 1) {
            $fileName = time().'_'.'section_icon'.'.'.$request->file->getClientOriginalExtension();
        } else {
            $fileName = $request->icon;
        }

        $section = new Section();
        $section->section_title = $request->title;
        $section->section_subtitle = $request->subtitle;
        $section->color = $request->color;
        if($request->icon_type_id == 2) {
            $section->icon = $fileName.' home_icon_size2';
        } else if($request->icon_type_id == 3) {
            $section->icon = 'icon ion-'.$fileName.' home_icon_size2';
        }
        
        $section->icon_type_id = $request->icon_type_id;
        $section->status = 1;
        $section->iframe = $request->iframe;
        $section->position = $request->position;
        $section->google_tag = 'section_' . $request->google_tag;
        $section->link_question_id = $request->link_question_id;
        $section->direct_content_question_id = $request->direct_content_question_id;
        $section->url = $request->url;

        if($request->video_id != '') {
            $word = "/";
            $string = $request->video_id;
 
            if(strpos($string, $word) !== false){
                $word = "=";
                $string = $request->video_id;

                if(strpos($string, $word) !== false){
                    $video = explode("=", $request->video_id);
                    $video = explode("&", $video[1]);
                    $section->video_id = trim($video[0]);
                } else {
                    $video = explode("/", $request->video_id);
                    $section->video_id = trim($video[3]);
                }
            } else{
                $section->video_id = trim($request->video_id);
            }
        }

        $move_position_sections = Section::where('position', '>=', $request->position)->orderBy('position', 'ASC')->get();
        $position = $request->position;
        foreach($move_position_sections as $move_position_section) {
            $position = $position + 1;
            $detail_section = Section::find($move_position_section->section_id);
            $detail_section->position = $position;
            $detail_section->save();
        }

        if($section->save()) {
            if($request->direct_content_question_id == 1) {
                $category = new Category;
                $category->alliance_id = 0;
                $category->section_id = $section->section_id;
                $category->highlight_id = 0;
                $category->name = 'Sin Categoría para la Sección '. $section->section_title;
                $category->position = 1;
                $category->status = 1;
                $category->save();
            }

            if($request->icon_type_id == 1) {
                Storage::disk('local')->putFileAs(
                    '/public',
                    $request->file,
                    $fileName
                );
            }

            return $this->successResponse($section);
        } else {
            return $this->errorResponse($section);
        }
    }

    /**
     * A listing of the resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        if($this->user->rol_id == 2) {
            $sections = Section::from('sections as c')
                        ->selectRaw('c.*')
                        ->leftJoin('categories', 'categories.section_id', '=', 'c.section_id')
                        ->where('alliance_id', $this->user->alliance_id)
                        ->get();
        } else {
            $sections = Section::where('status', 1)->orderBy('section_title', 'ASC')
                        ->get();
        }
        
        return $this->successResponse($sections);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $section = Section::find($id);

        return $this->successResponse($section);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function poll(Request $request)
    {
        $id = $request->segment(4);

        $section = Poll::where('section_id', $id)->where('status', 1)->first();
        $section_qty = Poll::where('section_id', $id)->where('status', 1)->count();
        
        if($section_qty > 0) {
            if($section->category_id != null) {
                return $this->successResponse($section->category_id);
            } else {
                return $this->successResponse('0');
            }
        } else {
            return $this->successResponse('');
        }
    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function move(Request $request)
    {
        $id = $request->segment(4);
        $position = $request->segment(5) + 1;

        $fix_sections = Section::orderBy('position', 'ASC')->get();

        $i = 1;

        foreach($fix_sections as $fix_section) {
            $section_detail = Section::find($fix_section->section_id);
            $section_detail->position = $i;
            $section_detail->save();
            $i = $i + 1;
        }

        $another_section = Section::where('position', $position)->first();
        $section = Section::find($id);

        if($section->position > $another_section->position) {
            $section->position = $position;

            $another_section->position = $another_section->position + 1;
        } else {
            $section->position = $position;

            $another_section->position = $another_section->position - 1;
        }
        
        $another_section->save();
        $section->save();
        

        return $this->errorResponse($section);
    }

    /**
     * Update the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if($request->icon_type_id == 1) {
            if($request->file != 'undefined') {
                $fileName = time().'_'.'section_icon'.'.'.$request->file->getClientOriginalExtension();
            }
        } else {
            $fileName = $request->icon;
        }

        $section = Section::find($id);
        $section->section_subtitle = $request->subtitle;
        $section->section_title = $request->title;
        if($request->iframe_question_id == 1) {
            $section->iframe = $request->iframe;
        } else {
            $section->iframe = '';
        }
        $section->color = $request->color;
        $old_icon_type = $section->icon_type_id;
        $old_icon_name = $section->icon;
        $section->icon_type_id = $request->icon_type_id;
        $section->google_tag = 'section_' . $request->google_tag;
        $section->link_question_id = $request->link_question_id;
        $section->direct_content_question_id = $request->direct_content_question_id;
        $section->url = $request->url;

        $word = "/";
        $string = $request->video_id;
        
        if($request->youtube_question_id == 1) {
            if($string != '') {
                if(strpos($string, $word) !== false){
                    $word = "=";
                    $string = $request->video_id;
        
                    if(strpos($string, $word) !== false){
                        $video = explode("=", $request->video_id);
                        $video = explode("&", $video[1]);
                        $section->video_id = $video[0];
                    } else {
                        $video = explode("/", $request->video_id);
                        $section->video_id = $video[3];
                    }
                } else{
                    $section->video_id = $request->video_id;
                }
            }
        } else {
            $section->video_id = '';
        }

        if($request->icon_type_id == 2) {
            $section->icon = $fileName.' home_icon_size2';
        } else if($request->icon_type_id == 3) {
            $section->icon = 'icon ion-'.$fileName.' home_icon_size2';
        }

        $old_position = $section->position;
        $section->position = $request->position;

        if($old_position != $request->position) {
            $move_position_sections = Section::where('position', '>=', $request->position)->orderBy('position', 'ASC')->get();
            $position = $request->position;
            foreach($move_position_sections as $move_position_section) {
                $position = $position + 1;
                $detail_section = Section::find($move_position_section->section_id);
                $detail_section->position = $position;
                $detail_section->save();
            }
        }

        if($section->save()) {
            if($request->file != 'undefined') {
                if($request->icon_type_id == 1) {
                    Storage::disk('local')->putFileAs(
                        '/public',
                        $request->file,
                        $fileName
                    );
                }
            }

            if($request->icon_type_id == 2 && $old_icon_type == 1) {
                if(Storage::exists('public/'.$old_icon_name)) {
                    Storage::delete('public/'.$old_icon_name);
                }
            }

            return $this->successResponse($section);
        } else {
            return $this->errorResponse($section);
        }
    }

    /**
     * Destroy the specified resource.
     *
     * @param  int  $id
     * @return \App\Http\Controllers\ApiResponseController
     */
    public function destroy($id)
    {
        $check_section_categories = Category::where('section_id', $id)->count();

        if($check_section_categories == 0) {
            $section = Section::find($id);
            if(Storage::exists('public/'.$section->icon)) {
                if(Storage::delete('public/'.$section->icon)) {
                    if($section->delete()) {
                        $fix_sections = Section::orderBy('position', 'ASC')->get();

                        $i = 1;

                        foreach($fix_sections as $fix_section) {
                            $section_detail = Section::find($fix_section->section_id);
                            $section_detail->position = $i;
                            $section_detail->save();
                            $i = $i + 1;
                        }

                        return $this->successResponse($section);
                    } else {
                        return $this->errorResponse($section);
                    }
                }
            } else {
                if($section->delete()) {
                    $fix_sections = Section::orderBy('position', 'ASC')->get();

                    $i = 1;

                    foreach($fix_sections as $fix_section) {
                        $section_detail = Section::find($fix_section->section_id);
                        $section_detail->position = $i;
                        $section_detail->save();
                        $i = $i + 1;
                    }

                    return $this->successResponse($section);
                } else {
                    return $this->errorResponse($section);
                }
            }
        } else {
            $section = Section::find($id);
            $section->status = 0;
            if($section->save()) {
                return $this->successResponse($section);
            } else {
                return $this->errorResponse($section);
            }
        }
    }

    /**
     * Activate the specified resource.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request)
    {
        $id = $request->segment(4);

        $section = Section::where('section_id', $id)->first();
        $section->status = 1;
        if($section->save()) {
            return $this->successResponse($section);
        } else {
            return $this->errorResponse($section);
        }
    }
}