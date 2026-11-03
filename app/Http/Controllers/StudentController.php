<?php
namespace App\Http\Controllers;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class StudentController extends Controller
{
    public function index(Request $request) {
        $search = trim((string) $request->query('search',''));
        $students = Student::query()->when($search !== '', function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                foreach (['student_number','first_name','last_name','email','course'] as $column) {
                    $query->orWhereRaw('LOWER('.$column.') LIKE ?', ['%'.mb_strtolower($search).'%']);
                }
            });
        })->orderBy('last_name')->orderBy('first_name')->paginate(10)->withQueryString();
        return view('students.index', compact('students','search'));
    }
    public function create() { return view('students.form',['student'=>new Student]); }
    public function show(Student $student) { return view('students.show',compact('student')); }
    public function edit(Student $student) { return view('students.form',compact('student')); }
    public function store(Request $request) {
        $student = Student::create($this->validated($request));
        return to_route('students.show',$student)->with('success','Student created successfully.');
    }
    public function update(Request $request, Student $student) {
        $student->update($this->validated($request,$student));
        return to_route('students.show',$student)->with('success','Student updated successfully.');
    }
    public function destroy(Student $student) {
        $student->delete();
        return to_route('students.index')->with('success','Student deleted successfully.');
    }
    private function validated(Request $request, ?Student $student=null): array {
        $request->merge(collect($request->only(['student_number','first_name','last_name','email','course']))->map(fn($v)=>is_string($v)?trim($v):$v)->all());
        return $request->validate([
            'student_number'=>['required','string','max:30',Rule::unique('students')->ignore($student)],
            'first_name'=>['required','string','max:100'],
            'last_name'=>['required','string','max:100'],
            'email'=>['required','email','max:255',Rule::unique('students')->ignore($student)],
            'course'=>['required','string','max:120'],
            'year_level'=>['required','integer','between:1,6'],
        ]);
    }
}