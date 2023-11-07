<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;

class VerifyIdController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:15,5');
    }

    public function verify (Request $request)
    {
        $user_id = Auth::id();
        $id_verified = Auth::user()->getIdVerified();

        if ($id_verified == 1) {
            return redirect()->route('home');
        }

        $country_code = Auth::user()->getCountry();
        $countries_options = getCountryOptions($country_code);

        if ($request->input('full_name')) {

            $this->validate($request, [
                'full_name' => 'required',
                'date_of_birth' => 'required',
                'country' => 'required',
                'photo_id' => 'required|file|image|max:20972', // 20972kb > 20mb
                'photo_document' => 'required|file|image|max:20972' // 20972kb > 20mb
            ]);

            $full_name = trim($request->input('full_name'));
            $date_of_birth = trim($request->input('date_of_birth'));
            $country_code = $request->input('country');
            $photo_id = $request->file('photo_id');
            $photo_document = $request->file('photo_document');

            // an identity can only be attached to 1 account
            $id_exists =
                DB::table('id_verification')
                    ->where(['date_of_birth' => $date_of_birth, 'full_name' => $full_name, 'country' => $country_code])
                    ->value('id');

            if ($id_exists != null) {
                return redirect()->route('verify_id')->with('alert', 'This identity is already attached to another account.<br>Contact support to transfer your identity to a different account.');
            }

            $countries = getCountryList();

            if (!isset($countries[$country_code])) {
                return redirect()->route('verify_id')->with('alert', 'Please select a valid country.');
            }

            $img = Image::make($photo_id);
            $img2 = Image::make($photo_document);

            $img_meta_data = $img->exif();
            $img2_meta_data = $img2->exif();

            $img_data = [
                'DateTime' => isset($img_meta_data['DateTime']) ? $img_meta_data['DateTime']: '',
                'Make' => isset($img_meta_data['Make']) ? $img_meta_data['Make']: '',
                'Model' => isset($img_meta_data['Model']) ? $img_meta_data['Model']: ''
            ];

            $img2_data = [
                'DateTime' => isset($img2_meta_data['DateTime']) ? $img2_meta_data['DateTime']: '',
                'Make' => isset($img2_meta_data['Make']) ? $img2_meta_data['Make']: '',
                'Model' => isset($img2_meta_data['Model']) ? $img2_meta_data['Model']: ''
            ];

            $img_data = json_encode($img_data);
            $img2_data = json_encode($img2_data);

            $img->resize(1024, null, function($constraint) {
                $constraint->aspectRatio();
            });

            $img->encode('jpg');

            $img2->resize(1024, null, function($constraint) {
                $constraint->aspectRatio();
            });

            $img2->encode('jpg');

            $random_str = str_random(40);
            $img_path = 'id_verification/'.$random_str.'.jpg';

            $random_str2 = str_random(40);
            $img2_path = 'id_verification/'.$random_str2.'.jpg';

            // upload photo id
            Storage::disk('s3')->put($img_path, (string) $img, 'public');

            // upload photo document
            Storage::disk('s3')->put($img2_path, (string) $img2, 'public');

            DB::beginTransaction();

            try {

                // save
                DB::table('id_verification')->insert(
                    [
                        'user_id' => $user_id,
                        'full_name' => $full_name,
                        'date_of_birth' => $date_of_birth,
                        'country' => $country_code,
                        'img' => $img_path,
                        'img_data' => $img_data,
                        'img2' => $img2_path,
                        'img2_data' => $img2_data
                    ]
                );

                // update user id_verified to 2 (meaning under review)
                Auth::user()->id_verified = 2;
                Auth::user()->save();

            } catch (\Exception $e) {

                DB::rollBack();
                Storage::disk('s3')->delete($img_path);
                Storage::disk('s3')->delete($img2_path);
                return redirect()->route('verify_id')->with('alert', 'Something went wrong, please try again.');
            }

            DB::commit();
            return redirect()->route('verify_id');
        }

        return view('pages.verify_id',
             [
                 'countries_options' => $countries_options,
                 'id_verified' => $id_verified
             ]
        );
    }

}