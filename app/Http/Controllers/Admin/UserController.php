<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use App\Models\User;

class UserController extends Controller
{	
    public function __construct(){
        $this->middleware('auth');
        $this->middleware('role:staff');
	}

    public function index(Request $request)
    {
        $querystring = $this->buildQueryString($request);
        $users = $this->buildIndexQuery($request);
        $page_title = $request->has('q') ? 'Pencarian ' . $request->q : 'Users';
        
        return view('admin.users.index', compact('users', 'querystring', 'page_title'));
    }

    public function buildQueryString($request)
    {
        $querystring = null;

        if( $request->has('type') ) {
            $querystring['type'] = $request->has('type') ? $request->type : '';
        } elseif($request->has('q')) {
            $querystring['q'] = $request->has('q') ? $request->q : '';
        }

        return $querystring;
    }

    public function buildIndexQuery($request)
    {
        if( $request->has('type') ) {            
            $users = User::where('role', $request->type)->orderBy('created_at', 'DESC')->paginate(7);
        } elseif( $request->has('q') ){
            $users = User::where(function($query) use ($request){
                        $query->where('no_induk', 'LIKE', '%' . $request->q . '%')   
                                ->orWhere('firstname', 'LIKE', '%' . $request->q . '%')                        
                                ->orWhere('lastname', 'LIKE', '%' . $request->q . '%')                      
                                ->orWhere('email', 'LIKE', '%' . $request->q . '%');
                    })->orderBy('created_at', 'DESC')->paginate(7);
        } else {
            $users = User::orderBy('created_at', 'DESC')->paginate(7);
        }

        return $users;
    }

    public function create(Request $request)
    {
        $users = User::orderBy('created_at', 'DESC')->paginate(7);

        $page_title = $request->has('type') ? ucfirst($request->type) : 'User';
        return view('admin.users.create', compact('users', 'page_title'));
    }

    public function store(Request $request)
    {        
        $this->validate($request, [
            'no_induk' => 'required|unique:users,no_induk',
            'username' => 'required|unique:users,username',
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email|unique:users,email'
        ], [
            'required' => 'Kolom :attribute diperlukan!',
            'exists' => 'Kolom :attribute tidak ditemukan!',
            'email' => 'Kolom :attribute harus berupa email.'
        ]);

        $user = User::create($request->all());

        $user->usermeta()->create([
            'picture' => 'icon-user-default.png',
            'cover' => 'cover-default.jpg',
            'dateofbirth' => $request->dateofbirth,
            'address' => $request->address,
            'telp_no' => $request->telp_no,
            'parent_telp_no' => $request->parent_telp_no
        ]);

        \Flash::success('User tersimpan.');
        return redirect()->route('lms-admin.users.edit', [$user->id]);
    }

    public function edit(Request $request, $id)
    {
        $users = User::orderBy('created_at', 'DESC')->paginate(7);
        $page_title = 'Edit User';
        $user = User::findOrFail($id);

        return view('admin.users.edit', compact('users', 'user', 'page_title'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $this->validate($request, [
            'no_induk' => 'required|unique:users,no_induk,' . $user->id,
            'username' => 'required|unique:users,username,' . $user->id,
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id
        ], [
            'required' => 'Kolom :attribute diperlukan!',
            'exists' => 'Kolom :attribute tidak ditemukan!',
            'email' => 'Kolom :attribute harus berupa email.'
        ]);
        
        $user->update($request->all());

        $user->usermeta()->update([
            'dateofbirth' => $request->dateofbirth,
            'address' => $request->address,
            'telp_no' => $request->telp_no,
            'parent_telp_no' => $request->parent_telp_no
        ]);

        \Flash::success('User diperbaharui.');
        return redirect()->back();
    }
    
    public function destroy($id)
    {
        User::find($id)->delete();

        \Flash::success('User berhasil dihapus.');
        return redirect()->back();
    }

}