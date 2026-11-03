<?php
namespace Tests\Feature;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class StudentCrudTest extends TestCase
{
    use RefreshDatabase;
    private function data(): array {
        return ['student_number'=>'TEST-001','first_name'=>'Test','last_name'=>'Student','email'=>'test@example.test','course'=>'Computer Science','year_level'=>2];
    }
    public function test_complete_student_lifecycle(): void {
        $this->get('/')->assertRedirect('/students');
        $this->get('/students')->assertOk()->assertSee('Your student directory starts here');
        $this->get('/students/create')->assertOk();
        $this->post('/students',$this->data())->assertSessionHasNoErrors();
        $student = Student::sole();
        $this->assertDatabaseHas('students',$this->data());
        $this->get('/students/'.$student->id)->assertOk()->assertSee('TEST-001');
        $this->get('/students/'.$student->id.'/edit')->assertOk();
        $this->put('/students/'.$student->id,array_replace($this->data(),['course'=>'Information Technology']))->assertRedirect(route('students.show',$student));
        $this->assertDatabaseHas('students',['id'=>$student->id,'course'=>'Information Technology']);
        $this->get('/students?search=information')->assertOk()->assertSee('TEST-001');
        $this->get('/students?search=missing')->assertOk()->assertDontSee('TEST-001');
        $this->delete('/students/'.$student->id)->assertRedirect(route('students.index'));
        $this->assertDatabaseMissing('students',['id'=>$student->id]);
        $this->get('/students/'.$student->id)->assertNotFound();
    }
    public function test_validation_and_unique_fields(): void {
        $this->post('/students',[])->assertSessionHasErrors(['student_number','first_name','last_name','email','course','year_level']);
        Student::create($this->data());
        $this->post('/students',$this->data())->assertSessionHasErrors(['student_number','email']);
        $this->post('/students',array_replace($this->data(),['student_number'=>'TEST-002','email'=>'bad','year_level'=>7]))->assertSessionHasErrors(['email','year_level']);
        $student = Student::sole();
        $this->put('/students/'.$student->id,$this->data())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('students',1);
    }
    public function test_blade_escapes_user_content(): void {
        $student = Student::create(array_replace($this->data(),['first_name'=>'<script>alert(1)</script>']));
        $this->get('/students/'.$student->id)->assertOk()->assertDontSee('<script>alert(1)</script>',false)->assertSee('&lt;script&gt;',false);
    }
}