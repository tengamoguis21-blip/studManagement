<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
 public function up(): void
 {
 if (DB::getDriverName() !== 'pgsql') {
 return;
 }
 DB::unprepared(<<<'SQL'
-- Classroom demonstration: anonymous clients can manage fictional students.
ALTER TABLE public.students ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON public.students FROM anon, authenticated;
GRANT SELECT, DELETE ON public.students TO anon;
GRANT INSERT (student_number, first_name, last_name, email, course, year_level),
 UPDATE (student_number, first_name, last_name, email, course, year_level)
 ON public.students TO anon;
REVOKE ALL ON SEQUENCE public.students_id_seq FROM anon, authenticated;
GRANT USAGE, SELECT ON SEQUENCE public.students_id_seq TO anon;
CREATE POLICY student_demo_read ON public.students FOR SELECT TO anon
 USING (true);
CREATE POLICY student_demo_insert ON public.students FOR INSERT TO anon
 WITH CHECK (true);
CREATE POLICY student_demo_update ON public.students FOR UPDATE TO anon
 USING (true)
 WITH CHECK (true);
CREATE POLICY student_demo_delete ON public.students FOR DELETE TO anon
 USING (true);
CREATE FUNCTION public.student_app_validate_row() RETURNS trigger
LANGUAGE plpgsql SET search_path = '' AS $$
BEGIN
 NEW.student_number := btrim(NEW.student_number);
 NEW.first_name := btrim(NEW.first_name);
 NEW.last_name := btrim(NEW.last_name);
 NEW.email := btrim(NEW.email);
 NEW.course := btrim(NEW.course);
 IF NEW.student_number IS NULL OR char_length(NEW.student_number) NOT BETWEEN 1 AND 30
 OR NEW.first_name IS NULL OR char_length(NEW.first_name) NOT BETWEEN 1 AND 100
 OR NEW.last_name IS NULL OR char_length(NEW.last_name) NOT BETWEEN 1 AND 100
 OR NEW.course IS NULL OR char_length(NEW.course) NOT BETWEEN 1 AND 120
 OR NEW.email IS NULL OR char_length(NEW.email) > 255
 OR NEW.email !~ '^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$'
 OR NEW.year_level IS NULL OR NEW.year_level NOT BETWEEN 1 AND 6 THEN
 RAISE EXCEPTION 'Student fields are invalid. Check required fields, email and year level.' USING ERRCODE = '23514';
 END IF;
 IF TG_OP = 'INSERT' THEN
 NEW.created_at := COALESCE(NEW.created_at, timezone('utc', now()));
 ELSE
 NEW.created_at := OLD.created_at;
 END IF;
 NEW.updated_at := timezone('utc', clock_timestamp());
 RETURN NEW;
END;
$$;
REVOKE ALL ON FUNCTION public.student_app_validate_row() FROM PUBLIC, anon, authenticated;
CREATE TRIGGER student_app_validate_before_write BEFORE INSERT OR UPDATE ON public.students
 FOR EACH ROW EXECUTE FUNCTION public.student_app_validate_row();
CREATE FUNCTION public.student_app_search(search_term text DEFAULT '', offset_rows integer DEFAULT 0, page_size
 integer DEFAULT 21)
RETURNS SETOF public.students LANGUAGE sql STABLE SECURITY INVOKER SET search_path = '' AS $$
 SELECT * FROM public.students
 WHERE COALESCE(search_term, '') = ''
 OR strpos(lower(student_number), lower(search_term)) > 0
 OR strpos(lower(first_name || ' ' || last_name), lower(search_term)) > 0
 OR strpos(lower(email), lower(search_term)) > 0
 OR strpos(lower(course), lower(search_term)) > 0
 ORDER BY last_name, first_name, id
 LIMIT greatest(1, least(COALESCE(page_size, 21), 101)) OFFSET greatest(0, COALESCE(offset_rows, 0));
$$;
REVOKE ALL ON FUNCTION public.student_app_search(text, integer, integer) FROM PUBLIC, anon;
GRANT EXECUTE ON FUNCTION public.student_app_search(text, integer, integer) TO anon;
NOTIFY pgrst, 'reload schema';
SQL);
 }
 public function down(): void
 {
 if (DB::getDriverName() !== 'pgsql') {
 return;
 }
 DB::unprepared(<<<'SQL'
DROP POLICY IF EXISTS student_demo_read ON public.students;
DROP POLICY IF EXISTS student_demo_insert ON public.students;
DROP POLICY IF EXISTS student_demo_update ON public.students;
DROP POLICY IF EXISTS student_demo_delete ON public.students;
DROP TRIGGER IF EXISTS student_app_validate_before_write ON public.students;
DROP FUNCTION IF EXISTS public.student_app_search(text, integer, integer);
DROP FUNCTION IF EXISTS public.student_app_validate_row();
REVOKE ALL ON public.students FROM anon;
REVOKE INSERT (student_number, first_name, last_name, email, course, year_level), UPDATE (student_number, first_name, last_name, email, course, year_level) ON public.students FROM anon;
REVOKE USAGE, SELECT ON SEQUENCE public.students_id_seq FROM anon, authenticated;
NOTIFY pgrst, 'reload schema';
SQL);
 }
};