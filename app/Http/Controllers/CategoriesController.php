<?php namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Category as Category;
use App\Models\Company;
use App\Models\Setting;
use Auth;
use DB;
use Input;
use Lang;
use Redirect;
use Str;
use View;

class CategoriesController extends Controller
{
    /**
     * Show a list of all the categories.
     *
     * @return View
     */

    public function getIndex()
    {
        // Show the page
        return View::make('categories/index');
    }


    /**
     * Category create.
     *
     * @return View
     */
    public function getCreate()
    {
        // Show the page
         $category_types= Helper::categoryTypeList();
        return View::make('categories/edit')->with('category', new Category)
        ->with('category_types', $category_types);
    }


    /**
     * Category create form processing.
     *
     * @return Redirect
     */
    public function postCreate()
    {

        // create a new model instance
        $category = new Category();

        // Update the category data
        $category->name                 = e(Input::get('name'));
        $category->category_type        = e(Input::get('category_type'));
        $category->eula_text            = e(Input::get('eula_text'));
        $category->use_default_eula     = e(Input::get('use_default_eula', '0'));
        $category->require_acceptance   = e(Input::get('require_acceptance', '0'));
        $category->checkin_email        = e(Input::get('checkin_email', '0'));
        $category->user_id              = Auth::user()->id;

        if ($category->save()) {
        // Redirect to the new category  page
            return Redirect::to("admin/settings/categories")->with('success', Lang::get('admin/categories/message.create.success'));
        } else {

          // The given data did not pass validation
            return Redirect::back()->withInput()->withErrors($category->getErrors());

        }

        // Redirect to the category create page
        return Redirect::to('admin/settings/categories/create')->with('error', Lang::get('admin/categories/message.create.error'));


    }

    /**
     * Category update.
     *
     * @param  int  $categoryId
     * @return View
     */
    public function getEdit($categoryId = null)
    {
        // Check if the category exists
        if (is_null($category = Category::find($categoryId))) {
            // Redirect to the blogs management page
            return Redirect::to('admin/settings/categories')->with('error', Lang::get('admin/categories/message.does_not_exist'));
        }

        // Show the page
        //$category_options = array('' => 'Top Level') + Category::lists('name', 'id');

        $category_options = array('' => 'Top Level') + DB::table('categories')->where('id', '!=', $categoryId)->lists('name', 'id');
        $category_types= Helper::categoryTypeList();

        return View::make('categories/edit', compact('category'))
        ->with('category_options', $category_options)
        ->with('category_types', $category_types);
    }


    /**
     * Category update form processing page.
     *
     * @param  int  $categoryId
     * @return Redirect
     */
    public function postEdit($categoryId = null)
    {
        // Check if the blog post exists
        if (is_null($category = Category::find($categoryId))) {
            // Redirect to the blogs management page
            return Redirect::to('admin/categories')->with('error', Lang::get('admin/categories/message.does_not_exist'));
        }

        // Update the category data
        $category->name            = e(Input::get('name'));
        $category->category_type        = e(Input::get('category_type'));
        $category->eula_text            = e(Input::get('eula_text'));
        $category->use_default_eula     = e(Input::get('use_default_eula', '0'));
        $category->require_acceptance   = e(Input::get('require_acceptance', '0'));
        $category->checkin_email        = e(Input::get('checkin_email', '0'));

        if ($category->save()) {
        // Redirect to the new category page
            return Redirect::to("admin/settings/categories")->with('success', Lang::get('admin/categories/message.update.success'));
        } // attempt validation
        else {
          // The given data did not pass validation
            return Redirect::back()->withInput()->withErrors($category->getErrors());
        }

        // Redirect to the category management page
        return Redirect::back()->with('error', Lang::get('admin/categories/message.update.error'));

    }

    /**
     * Delete the given category.
     *
     * @param  int  $categoryId
     * @return Redirect
     */
    public function getDelete($categoryId)
    {
        // Check if the category exists
        if (is_null($category = Category::find($categoryId))) {
            // Redirect to the blogs management page
            return Redirect::to('admin/settings/categories')->with('error', Lang::get('admin/categories/message.not_found'));
        }


        if ($category->has_models() > 0) {

            // Redirect to the asset management page
            return Redirect::to('admin/settings/categories')->with('error', Lang::get('admin/categories/message.assoc_users'));
        } else {

            $category->delete();

            // Redirect to the locations management page
            return Redirect::to('admin/settings/categories')->with('success', Lang::get('admin/categories/message.delete.success'));
        }


    }



    /**
    *  Get the asset information to present to the category view page
    *
    * @param  int  $assetId
    * @return View
    **/
    public function getView($categoryID = null)
    {
        $category = Category::find($categoryID);

        if (isset($category->id)) {
                return View::make('categories/view', compact('category'));
        } else {
            // Prepare the error message
            $error = Lang::get('admin/categories/message.does_not_exist', compact('id'));

            // Redirect to the user management page
            return Redirect::route('categories')->with('error', $error);
        }


    }

    public function getDatatable()
    {
        // Grab all the categories
        $categories = Category::with('assets', 'accessories', 'consumables');

        if (Input::has('search')) {
            $categories = $categories->TextSearch(e(Input::get('search')));
        }

        if (Input::has('offset')) {
            $offset = e(Input::get('offset'));
        } else {
            $offset = 0;
        }

        if (Input::has('limit')) {
            $limit = e(Input::get('limit'));
        } else {
            $limit = 50;
        }


        $allowed_columns = ['id','name','category_type'];
        $order = Input::get('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array(Input::get('sort'), $allowed_columns) ? e(Input::get('sort')) : 'created_at';

        $categories = $categories->orderBy($sort, $order);

        $catCount = $categories->count();
        $categories = $categories->skip($offset)->take($limit)->get();

        $rows = array();

        foreach ($categories as $category) {
            $actions = '<a href="'.route('update/category', $category->id).'" class="btn btn-warning btn-sm" style="margin-right:5px;"><i class="fa fa-pencil icon-white"></i></a><a data-html="false" class="btn delete-asset btn-danger btn-sm" data-toggle="modal" href="'.route('delete/category', $category->id).'" data-content="'.Lang::get('admin/categories/message.delete.confirm').'" data-title="'.Lang::get('general.delete').' '.htmlspecialchars($category->name).'?" onClick="return false;"><i class="fa fa-trash icon-white"></i></a>';
            $rows[] = array(
                'id'      => $category->id,
                'name'  => (string)link_to('/admin/settings/categories/'.$category->id.'/view', $category->name) ,
                'category_type' => ucwords($category->category_type),
                'count'         => $category->assets->count(),
                'acceptance'    => ($category->require_acceptance=='1') ? '<i class="fa fa-check"></i>' : '',
                //EULA is still not working correctly
                'eula'          => ($category->getEula()) ? '<i class="fa fa-check"></i>' : '',
                'actions'       => $actions
            );
        }

        $data = array('total' => $catCount, 'rows' => $rows);

        return $data;
    }

    public function getDataView($categoryID)
    {

        $category = Category::find($categoryID);

        if ($category->category_type =='asset') {
            $category_assets = $category->assets;
        } elseif ($category->category_type =='accessory') {
            $category_assets = $category->accessories;
        } elseif ($category->category_type =='consumable') {
            $category_assets = $category->consumables;
        } elseif ($category->category_type =='component') {
            $category_assets = $category->components;
        }


        if (Input::has('search')) {
            $category_assets = $category_assets->TextSearch(e(Input::get('search')));
        }

        if (Input::has('offset')) {
            $offset = e(Input::get('offset'));
        } else {
            $offset = 0;
        }

        if (Input::has('limit')) {
            $limit = e(Input::get('limit'));
        } else {
            $limit = 50;
        }

        $order = Input::get('order') === 'asc' ? 'asc' : 'desc';

        $allowed_columns = ['id','name','serial','asset_tag'];
        $sort = in_array(Input::get('sort'), $allowed_columns) ? Input::get('sort') : 'created_at';
        $count = $category_assets->count();

        $rows = array();

        foreach ($category_assets as $asset) {

            $actions = '';
            $inout='';

            if ($asset->deleted_at=='') {
                $actions = '<div style=" white-space: nowrap;"><a href="'.route('clone/hardware', $asset->id).'" class="btn btn-info btn-sm" title="Clone asset"><i class="fa fa-files-o"></i></a> <a href="'.route('update/hardware', $asset->id).'" class="btn btn-warning btn-sm"><i class="fa fa-pencil icon-white"></i></a> <a data-html="false" class="btn delete-asset btn-danger btn-sm" data-toggle="modal" href="'.route('delete/hardware', $asset->id).'" data-content="'.Lang::get('admin/hardware/message.delete.confirm').'" data-title="'.Lang::get('general.delete').' '.htmlspecialchars($asset->asset_tag).'?" onClick="return false;"><i class="fa fa-trash icon-white"></i></a></div>';
            } elseif ($asset->deleted_at!='') {
                $actions = '<a href="'.route('restore/hardware', $asset->id).'" class="btn btn-warning btn-sm"><i class="fa fa-recycle icon-white"></i></a>';
            }

            if ($asset->assetstatus) {
                if ($asset->assetstatus->deployable != 0) {
                    if (($asset->assigned_to !='') && ($asset->assigned_to > 0)) {
                        $inout = '<a href="'.route('checkin/hardware', $asset->id).'" class="btn btn-primary btn-sm">'.Lang::get('general.checkin').'</a>';
                    } else {
                        $inout = '<a href="'.route('checkout/hardware', $asset->id).'" class="btn btn-info btn-sm">'.Lang::get('general.checkout').'</a>';
                    }
                }
            }

            $rows[] = array(
            'id' => $asset->id,
            'name' => (string)link_to('/hardware/'.$asset->id.'/view', e($asset->name)),
            //'model' => $asset->model->name,
            'asset_tag' => e($asset->asset_tag),
            'serial' => e($asset->serial),
            'assigned_to' => ($asset->assigneduser) ? (string)link_to(config('app.url').'/admin/users/'.$asset->assigneduser->id.'/view', $asset->assigneduser->fullName()): '',
            'change' => $inout,
            'actions' => $actions,
            'companyName' => Company::getName($asset),
            );
        }

        $data = array('total' => $count, 'rows' => $rows);
        return $data;
    }
}