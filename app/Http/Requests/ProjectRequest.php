<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // sesuaikan dengan gate/permission jika perlu
    }

    public function rules(): array
    {
        return [
            'project_name'      => 'required|string|max:255',
            'project_location'  => 'required|string',
            'province_id'       => 'required|integer',
            'city_id'           => 'required|integer',
            'district_id'       => 'required|integer',
            'sub_district_id'   => 'required|integer',
            'postal_code_id'    => 'required|integer',
            'employee_id'       => 'required|uuid',
            'customer_id'       => 'required|uuid',
            'affiliator_id'     => 'nullable|uuid',
            'start_date'        => 'required|date',
            'end_date'          => 'nullable|date|after_or_equal:start_date',
            'project_status'    => 'nullable|integer',
            'description'    => 'nullable|string',
            'project_type' => [
                'required',
                function ($attribute, $value, $fail) {
                    $exists = \DB::table('zhpicture.project_types')->where('id', $value)->exists();
                    if (! $exists) {
                        $fail('Jenis proyek tidak valid.');
                    }
                },
            ],
        ];
    }
}
