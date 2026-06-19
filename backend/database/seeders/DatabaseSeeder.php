<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Assignment;
use App\Models\Submission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $course1 = Course::create([
            'name' => 'Web前端开发',
            'code' => 'CS101',
            'description' => '学习HTML、CSS、JavaScript和Vue框架',
            'teacher_name' => '张老师',
            'semester' => '2026春季',
        ]);

        $course2 = Course::create([
            'name' => '后端开发入门',
            'code' => 'CS102',
            'description' => '学习PHP和Laravel框架',
            'teacher_name' => '李老师',
            'semester' => '2026春季',
        ]);

        $students = [
            ['name' => '王小明', 'student_no' => '2024001', 'email' => 'xiaoming@example.com', 'phone' => '13800138001', 'class_name' => '计算机2401'],
            ['name' => '李小红', 'student_no' => '2024002', 'email' => 'xiaohong@example.com', 'phone' => '13800138002', 'class_name' => '计算机2401'],
            ['name' => '张三', 'student_no' => '2024003', 'email' => 'zhangsan@example.com', 'phone' => '13800138003', 'class_name' => '计算机2401'],
            ['name' => '李四', 'student_no' => '2024004', 'email' => 'lisi@example.com', 'phone' => '13800138004', 'class_name' => '计算机2401'],
            ['name' => '王五', 'student_no' => '2024005', 'email' => 'wangwu@example.com', 'phone' => '13800138005', 'class_name' => '计算机2402'],
            ['name' => '赵六', 'student_no' => '2024006', 'email' => 'zhaoliu@example.com', 'phone' => '13800138006', 'class_name' => '计算机2402'],
            ['name' => '孙七', 'student_no' => '2024007', 'email' => 'sunqi@example.com', 'phone' => '13800138007', 'class_name' => '计算机2402'],
            ['name' => '周八', 'student_no' => '2024008', 'email' => 'zhouba@example.com', 'phone' => '13800138008', 'class_name' => '计算机2402'],
        ];

        $studentModels = [];
        foreach ($students as $s) {
            $studentModels[] = Student::create($s);
        }

        foreach ($studentModels as $index => $student) {
            Enrollment::create([
                'course_id' => $course1->id,
                'student_id' => $student->id,
                'enrolled_at' => now(),
                'status' => 'enrolled',
            ]);
            if ($index < 5) {
                Enrollment::create([
                    'course_id' => $course2->id,
                    'student_id' => $student->id,
                    'enrolled_at' => now(),
                    'status' => 'enrolled',
                ]);
            }
        }

        $rollCall1 = Assignment::create([
            'course_id' => $course1->id,
            'title' => '第1次课堂点名',
            'description' => '2026年6月19日课堂签到',
            'type' => 'roll_call',
            'due_date' => now(),
            'max_score' => 100,
            'is_published' => true,
        ]);

        $rollCall2 = Assignment::create([
            'course_id' => $course1->id,
            'title' => '第2次课堂点名',
            'description' => '2026年6月20日课堂签到',
            'type' => 'roll_call',
            'due_date' => now()->addDay(),
            'max_score' => 100,
            'is_published' => true,
        ]);

        $rollCall3 = Assignment::create([
            'course_id' => $course2->id,
            'title' => '第1次课堂点名',
            'description' => '2026年6月19日后端课堂签到',
            'type' => 'roll_call',
            'due_date' => now(),
            'max_score' => 100,
            'is_published' => true,
        ]);

        foreach ($studentModels as $index => $student) {
            if ($index < 3) {
                Submission::create([
                    'assignment_id' => $rollCall1->id,
                    'student_id' => $student->id,
                    'content' => '已到课',
                    'score' => 100,
                    'feedback' => '出勤正常',
                    'status' => 'graded',
                    'submitted_at' => now(),
                    'graded_at' => now(),
                ]);
            } elseif ($index === 3) {
                Submission::create([
                    'assignment_id' => $rollCall1->id,
                    'student_id' => $student->id,
                    'content' => '迟到',
                    'score' => 80,
                    'feedback' => '迟到10分钟',
                    'status' => 'graded',
                    'submitted_at' => now(),
                    'graded_at' => now(),
                ]);
            } elseif ($index === 4) {
                Submission::create([
                    'assignment_id' => $rollCall1->id,
                    'student_id' => $student->id,
                    'content' => '已签到',
                    'score' => null,
                    'feedback' => null,
                    'status' => 'pending',
                    'submitted_at' => now(),
                    'graded_at' => null,
                ]);
            }
        }
    }
}
