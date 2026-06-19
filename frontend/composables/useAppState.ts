import type { Student } from '~/types'

export type UserRole = 'teacher' | 'student'

interface AppState {
  role: UserRole
  currentStudent: Student | null
}

const state = reactive<AppState>({
  role: 'teacher',
  currentStudent: null,
})

const DEMO_STUDENTS: Student[] = [
  { id: 1, name: '王小明', student_no: '2024001', email: 'xiaoming@example.com', phone: '13800138001', class_name: '计算机2401' },
  { id: 2, name: '李小红', student_no: '2024002', email: 'xiaohong@example.com', phone: '13800138002', class_name: '计算机2401' },
  { id: 3, name: '张三', student_no: '2024003', email: 'zhangsan@example.com', phone: '13800138003', class_name: '计算机2401' },
  { id: 4, name: '李四', student_no: '2024004', email: 'lisi@example.com', phone: '13800138004', class_name: '计算机2401' },
  { id: 5, name: '王五', student_no: '2024005', email: 'wangwu@example.com', phone: '13800138005', class_name: '计算机2402' },
  { id: 6, name: '赵六', student_no: '2024006', email: 'zhaoliu@example.com', phone: '13800138006', class_name: '计算机2402' },
  { id: 7, name: '孙七', student_no: '2024007', email: 'sunqi@example.com', phone: '13800138007', class_name: '计算机2402' },
  { id: 8, name: '周八', student_no: '2024008', email: 'zhouba@example.com', phone: '13800138008', class_name: '计算机2402' },
]

export function useAppState() {
  function setRole(role: UserRole) {
    state.role = role
    if (role === 'teacher') {
      state.currentStudent = null
    } else if (role === 'student' && !state.currentStudent) {
      state.currentStudent = DEMO_STUDENTS[0]
    }
  }

  function setCurrentStudent(student: Student) {
    state.currentStudent = student
  }

  function isTeacher() {
    return state.role === 'teacher'
  }

  function isStudent() {
    return state.role === 'student'
  }

  return {
    state,
    setRole,
    setCurrentStudent,
    isTeacher,
    isStudent,
    DEMO_STUDENTS,
  }
}
