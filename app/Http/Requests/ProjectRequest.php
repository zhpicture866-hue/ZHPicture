<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'affiliator_id'     => 'nullable|uuid',
            'affiliator_id' => 'nullable|uuid', // sesuaikan nama tabel
            'customer_id' => ['required','uuid', Rule::exists(Customer::class,'id')],
            'employee_id' => ['required','uuid', Rule::exists(Employee::class,'id')],
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
