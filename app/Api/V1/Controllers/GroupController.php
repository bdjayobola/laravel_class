<?php

namespace App\Api\V1\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\User;
use App\Group;
use App\Group_member;
use Auth;
use App\Mail\GroupInvite;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    //create group
    public function create_group(Request $request)
    {
        //validate form inputs
        $validatedData = $request->validate([
            'group_name' => 'required',
            'amount' => 'required',
            'frequency' => 'required',
            'start_date' => 'required',
            'first_pay_date' => 'required',
            'payment_method' => 'required',
            'no_of_members' => 'required'
        ]);

        $currentDate = date('Y-m-d');
        $format_date = date("jS F, Y", strtotime($currentDate));

        //check if group exists before
        if (Group::where('group_name', $request->get('group_name'))->whereIn('group_status', [0, 1])->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Group Already Exists!'
            ], 200);
        }

        //check start date to current date
        if ($request->get('start_date') < $currentDate) {
            return response()->json([
                'status' => false,
                'message' => 'Start Date can not be less than ' . $format_date
            ], 200);
        } elseif ($request->get('first_pay_date') < $request->get('start_date')) {
            return response()->json([
                'status' => false,
                'message' => 'First Pay Date can not be less than Start Date'
            ], 200);
        } elseif ($request->get('no_of_members') < 2) {
            return response()->json([
                'status' => false,
                'message' => 'The Least no of Members is 2'
            ], 200);
        }

        //return Auth::user()->id;

        //insert into group table
        //group_no, code/link, group_status, user_id
        $group = new Group;
        $group->group_name  = $request->get('group_name');
        $group->group_no  = 'GROUP-' . mt_rand(10000000, 99999999); //8 random
        $group->amount  = $request->get('amount');
        $group->frequency  = strtolower($request->get('frequency'));
        $group->start_date  = $request->get('start_date');
        $group->first_pay_date  = $request->get('first_pay_date');
        $group->payment_method  = $request->get('payment_method');
        $group->code  = 'http://127.0.0.1:8000/group_invite/' . mt_rand(100000, 999999); //6 random
        $group->group_status  = 0;
        $group->no_of_members  = $request->get('no_of_members');
        $group->user_id  = Auth::user()->id;

        if ($group->save()) {
            //insert into group members table
            $last_inserted_id = $group->id;

            $group_members = new Group_member;
            $group_members->group_id = $last_inserted_id;
            $group_members->first_name = Auth::user()->first_name;
            $group_members->last_name = Auth::user()->last_name;
            $group_members->user_id = Auth::user()->id;
            $group_members->member_type = 'admin';
            $group_members->email = Auth::user()->email;
            $group_members->status = 1;
            $group_members->position = 1;
            $group_members->payment_status = 'pending';
            $group_members->expected_pay_date = $group->first_pay_date;
            $group_members->join_date = date('Y-m-d');

            $group_members->save();
            return response()->json([
                'status' => true,
                'message' => 'Group Created Successfully!'
            ], 201);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Group Creation Failed!'
            ], 200);
        }
    }



    //edit group only if no payments has been made on dat group (not done)



    //select group per current user
    public function fetch_group()
    {
        $fetch_group = Group::where('user_id', Auth::user()->id)->whereIn('group_status', [0, 1])->get();
        if (count($fetch_group) == 0) {
            return response()->json([
                'status' => false,
                'message' => 'No Group Found!'
            ], 404);
        }
        return $fetch_group;
    }






    //add members to specified group
    public function add_members(Request $request, $group_id)
    {
        //add members based on no of members in d group created
        //send emails to all d members
        //prevent group admin from entering his/her own name/email along side other members
        //ensure right no of members was added from d form

        $link = Group::where('id', $group_id)->value('code'); //group invite link

        $admin_name = strtoupper(Auth::user()->first_name . ' ' . Auth::user()->last_name); //group owner details

        $loan_data = json_decode($request->getContent(), true); //form inputs

        //prevent group owner email
        foreach ($loan_data as $user) {

            $new[] = $user['email'];
        }

        if (count(array_unique($new)) < count($new)) {
            // Array has duplicates
            return response()->json([
                'status' => false,
                'message' => 'Duplicate Emails Not Allowed!'
            ], 200);
        } elseif (in_array(Auth::user()->email, $new)) {
            //prevent group owner email
            return response()->json([
                'status' => false,
                'message' => 'Your Email is not allowed!'
            ], 200);
        }

        foreach ($loan_data as $user) {

            //send mail inside d loop
            $username = ucfirst($user['first_name'] . ' ' . $user['last_name']);
            Mail::to($user['email'])->send(new GroupInvite($link, $admin_name, $username));

            if (!Mail::failures()) {
                //if mail was sent successfully, then proceed with insertion
                //insert into group members table
                DB::table('group_members')->insert(['group_id' => $group_id, 'first_name' => $user['first_name'], 'last_name' => $user['last_name'], 'member_type' => 'member', 'email' => $user['email'], 'payment_status' => 'pending', 'created_at' => date('Y-m-d h:i:s')]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Add Members Failed,Please try again!'
                ], 200);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Members Added Successfully!'
        ], 200);
    }





    //manage group page




    //fetch group details for invited members (so dat dey can join or decline)
    public function member_groups()
    {
        $user_email = Auth::user()->email; //current logged in user

        $member_groups = Group_member::join('groups', 'group_members.group_id', '=', 'groups.id')->where([
            ['group_members.email', '=', $user_email],
            ['group_members.status', '=', 0]
        ])->select('groups.group_name', 'group_members.*')->get();

        if (count($member_groups) == 0) {
            //if query returns empty
            return response()->json([
                'status' => false,
                'message' => 'No Record Found!'
            ]);
        }
        return $member_groups;
    }







    //join group
    public function join_group($group_id)
    {
        //pending - 0, join group = 1, complete cycle = 2, reject group = 3, 
        //if total no members av completely joined,change group status to 1 (active)
        //send mail to group owner

        $user_id = Auth::user()->id; //current logged in user
        $user_email = Auth::user()->email; //current logged in user

        $no_of_members = Group::where('id', $group_id)->value('no_of_members'); //get no of members for d specified group
        $frequency = Group::where('id', $group_id)->value('frequency'); //get frequency for d specified group

        //get last position in group members table
        $check_position = Group_member::where([
            ['group_id', '=', $group_id],
            ['position', '>', 0]
        ])->get();

        foreach ($check_position as $data) {
            //pass position into array
            $new_array[] = $data->position;
            $new_array1[] = $data->expected_pay_date;
        }

        $position = $new_array[count($new_array) - 1] + 1; //increment last position value by one

        if ($frequency == "daily") {
            $expected_pay_date = date('Y-m-d', strtotime($new_array1[count($new_array1) - 1] . ' + 1 days')); //increment last position value by a day
        } elseif ($frequency == "weekly") {
            $expected_pay_date = date('Y-m-d', strtotime($new_array1[count($new_array1) - 1] . ' + 1 week')); //increment last position value by one week

        } elseif ($frequency == "monthly") {
            $expected_pay_date = date('Y-m-d', strtotime($new_array1[count($new_array1) - 1] . ' + 1 month')); //increment last position value by one month

        }

        //update group members table
        $update_group_members = Group_member::where([
            ['group_id', '=', $group_id],
            ['email', '=', $user_email]
        ])->update(['user_id' => $user_id, 'status' => 1, 'position' => $position, 'expected_pay_date' => $expected_pay_date, 'join_date' => date('Y-m-d'), 'updated_at' => date('Y-m-d h:i:s')]);

        //check no of members dat av joined to 
        $check_new_position = Group_member::where([
            ['group_id', '=', $group_id],
            ['position', '>', 0]
        ])->get();

        if ($no_of_members == count($check_new_position)) {
            //update group status to 1 (active)
            Group::where('id', $group_id)->update(['group_status' => 1]);
        }

        if ($update_group_members) {
            return response()->json([
                'status' => true,
                'message' => 'Request Successful!'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Request Failed!'
            ], 200);
        }
    }






    //reject group
    public function reject_group($group_id)
    {
        //pending - 0, join group = 1, complete cycle = 2, reject group = 3, 
        $user_id = Auth::user()->id; //current logged in user
        //update group members table
        $update_group_members = Group_member::where([
            ['group_id', '=', $group_id],
            ['user_id', '=', $user_id]
        ])->update(['status' => 3, 'position' => 0, 'updated_at' => date('Y-m-d h:i:s')]);

        if ($update_group_members) {
            return response()->json([
                'status' => true,
                'message' => 'Request Successful!'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Request Failed!'
            ], 200);
        }
    }

    //


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
