<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = Illuminate\Support\Facades\DB::connection();
if ($db->getDriverName() !== 'pgsql') { throw new RuntimeException('Use the configured Supabase PostgreSQL conn
 ection.'); }
$assert = function (bool $ok, string $message): void {
 if (!$ok) { throw new RuntimeException($message); }
 echo "PASS: $message\n";
};
$expectError = function (callable $operation, string $code) use ($db, $assert): void {
 $db->statement('SAVEPOINT expected_error');
 $caught = null;
 try { $operation(); } catch (Throwable $e) { $caught = $e; }
 $db->statement('ROLLBACK TO SAVEPOINT expected_error');
 $db->statement('RELEASE SAVEPOINT expected_error');
 $assert($caught !== null && (string) $caught->getCode() === $code, "Database rejects request with SQLSTATE
 $code");
};
$db->beginTransaction();
try {
 $db->statement('SET LOCAL ROLE anon');
 foreach (['users', 'sessions', 'password_reset_tokens'] as $table) {
 $p = $db->selectOne("SELECT has_table_privilege(current_user, ?, 'SELECT') AS allowed", ["public.$table
 "]);
 $assert(!$p->allowed, "Anonymous role cannot read $table");
 }
 $number = 'VERIFY-'.bin2hex(random_bytes(5));
 $email = strtolower($number).'@example.test';
 $row = $db->selectOne('INSERT INTO public.students (student_number, first_name, last_name, email, course, y
 ear_level) VALUES (?, ?, ?, ?, ?, ?) RETURNING *', [$number, ' Transaction ', 'Demo', $email, 'Test Cou
 rse', 2]);
 $assert($row->first_name === 'Transaction' && $row->created_at && $row->updated_at, 'Anonymous insert trims
 fields and fills timestamps');
 $found = $db->select('SELECT * FROM public.student_app_search(?, 0, 21)', [$number]);
 $assert(count($found) === 1 && $found[0]->id === $row->id, 'Search returns the saved record');
 $expectError(fn () => $db->statement('UPDATE public.students SET email = ? WHERE id = ?', ['invalid-email',
 $row->id]), '23514');
 $expectError(fn () => $db->statement('UPDATE public.students SET year_level = 7 WHERE id = ?', [$row->id]),
 '23514');
 $expectError(fn () => $db->statement('UPDATE public.students SET id = id WHERE id = ?', [$row->id]), '42501
 ');
 $expectError(fn () => $db->statement('INSERT INTO public.students (student_number, first_name, last_name, e
 mail, course, year_level) VALUES (?, ?, ?, ?, ?, ?)', [$number, 'Duplicate', 'Demo', 'duplicate.'.$email,
 'Test', 1]), '23505');
 $expectError(fn () => $db->statement('INSERT INTO public.students (student_number, first_name, last_name, e
 mail, course, year_level) VALUES (?, ?, ?, ?, ?, ?)', [$number.'X', 'Duplicate', 'Demo', $email, 'Test',
 1]), '23505');
 $updated = $db->selectOne('UPDATE public.students SET course = ? WHERE id = ? RETURNING *', ['Updated Cours
 e', $row->id]);
 $assert($updated->course === 'Updated Course' && $updated->created_at === $row->created_at, 'Anonymous upda
 te works and preserves creation time');
 $assert($db->delete('DELETE FROM public.students WHERE id = ?', [$row->id]) === 1, 'Anonymous delete works'
 );
 $assert(count($db->select('SELECT * FROM public.students WHERE id = ?', [$row->id])) === 0, 'Deleted fixtur
 e is absent');
} finally {
 $db->rollBack();
}
echo "All checks passed. Transaction rolled back; existing records were untouched.\n";