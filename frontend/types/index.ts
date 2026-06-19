export interface Course {
  id: number
  name: string
  code: string
  description?: string
  teacher_name?: string
  semester?: string
  enrollments_count?: number
  assignments_count?: number
  enrollments?: Enrollment[]
  assignments?: Assignment[]
  created_at?: string
  updated_at?: string
}

export interface Student {
  id: number
  name: string
  student_no: string
  email?: string
  phone?: string
  class_name?: string
  enrollments_count?: number
  enrollments?: Enrollment[]
  submissions?: Submission[]
  created_at?: string
  updated_at?: string
}

export interface Enrollment {
  id: number
  course_id: number
  student_id: number
  enrolled_at?: string
  status: string
  course?: Course
  student?: Student
  created_at?: string
  updated_at?: string
}

export interface Assignment {
  id: number
  course_id: number
  title: string
  description?: string
  type: string
  due_date?: string
  max_score: number
  is_published: boolean
  submissions_count?: number
  course?: Course
  submissions?: Submission[]
  stats?: AssignmentStats
  created_at?: string
  updated_at?: string
}

export interface AssignmentStats {
  total_enrolled: number
  submitted: number
  missing: number
  graded: number
}

export interface Submission {
  id?: number
  assignment_id: number
  student_id: number
  content?: string | null
  score?: number | null
  feedback?: string | null
  status: 'pending' | 'graded' | 'absent'
  submitted_at?: string | null
  graded_at?: string | null
  assignment?: Assignment
  student?: Student
}

export interface AttendanceSession {
  id: number
  course_id: number
  title: string
  session_date?: string
  status: string
  records_count?: number
  course?: Course
  records?: AttendanceRecord[]
  created_at?: string
  updated_at?: string
}

export interface AttendanceRecord {
  id: number
  attendance_session_id: number
  student_id: number
  status: string
  remark?: string
  student?: Student
  created_at?: string
  updated_at?: string
}
