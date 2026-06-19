import type { Course, Student, Enrollment, Assignment, Submission, AttendanceSession } from '~/types'

const BASE_URL = '/api'

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const config = useRuntimeConfig()
  const base = config.public.apiBase || BASE_URL

  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }

  const finalOptions: RequestInit = {
    ...options,
    headers: {
      ...defaultHeaders,
      ...(options.headers || {}),
    },
  }

  const response = await fetch(`${base}${path}`, finalOptions)

  if (!response.ok) {
    let message = `请求失败 (${response.status})`
    try {
      const data = await response.json()
      if (data.message) message = data.message
    } catch {
      // ignore
    }
    throw new Error(message)
  }

  if (response.status === 204) {
    return null as unknown as T
  }

  return await response.json() as T
}

export function useApi() {
  return {
    courses: {
      list: () => request<Course[]>('/courses'),
      get: (id: number) => request<Course>(`/courses/${id}`),
      create: (data: Partial<Course>) => request<Course>('/courses', { method: 'POST', body: JSON.stringify(data) }),
      update: (id: number, data: Partial<Course>) => request<Course>(`/courses/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
      delete: (id: number) => request<void>(`/courses/${id}`, { method: 'DELETE' }),
    },
    students: {
      list: () => request<Student[]>('/students'),
      get: (id: number) => request<Student>(`/students/${id}`),
      create: (data: Partial<Student>) => request<Student>('/students', { method: 'POST', body: JSON.stringify(data) }),
      update: (id: number, data: Partial<Student>) => request<Student>(`/students/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
      delete: (id: number) => request<void>(`/students/${id}`, { method: 'DELETE' }),
    },
    enrollments: {
      list: (params?: { course_id?: number; student_id?: number }) => {
        const query = params ? '?' + new URLSearchParams(params as Record<string, string>).toString() : ''
        return request<Enrollment[]>(`/enrollments${query}`)
      },
      get: (id: number) => request<Enrollment>(`/enrollments/${id}`),
      create: (data: Partial<Enrollment>) => request<Enrollment>('/enrollments', { method: 'POST', body: JSON.stringify(data) }),
      update: (id: number, data: Partial<Enrollment>) => request<Enrollment>(`/enrollments/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
      delete: (id: number) => request<void>(`/enrollments/${id}`, { method: 'DELETE' }),
    },
    assignments: {
      list: (params?: { course_id?: number; type?: string }) => {
        const query = params ? '?' + new URLSearchParams(params as Record<string, string>).toString() : ''
        return request<Assignment[]>(`/assignments${query}`)
      },
      get: (id: number) => request<Assignment>(`/assignments/${id}`),
      create: (data: Partial<Assignment>) => request<Assignment>('/assignments', { method: 'POST', body: JSON.stringify(data) }),
      update: (id: number, data: Partial<Assignment>) => request<Assignment>(`/assignments/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
      delete: (id: number) => request<void>(`/assignments/${id}`, { method: 'DELETE' }),
    },
    submissions: {
      list: (params?: { assignment_id?: number; student_id?: number; status?: string }) => {
        const query = params ? '?' + new URLSearchParams(params as Record<string, string>).toString() : ''
        return request<Submission[]>(`/submissions${query}`)
      },
      get: (id: number) => request<Submission>(`/submissions/${id}`),
      create: (data: Partial<Submission>) => request<Submission>('/submissions', { method: 'POST', body: JSON.stringify(data) }),
      grade: (id: number, data: { score?: number; feedback?: string; status?: string }) =>
        request<Submission>(`/submissions/${id}/grade`, { method: 'POST', body: JSON.stringify(data) }),
      markAbsent: (data: { assignment_id: number; student_id: number; feedback?: string }) =>
        request<Submission>('/submissions/mark-absent', { method: 'POST', body: JSON.stringify(data) }),
      batchGrade: (data: { assignment_id: number; grades: Array<{ student_id: number; score?: number; feedback?: string; status?: string }> }) =>
        request<{ graded_count: number; results: Submission[] }>('/submissions/batch-grade', { method: 'POST', body: JSON.stringify(data) }),
      update: (id: number, data: Partial<Submission>) =>
        request<Submission>(`/submissions/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
      delete: (id: number) => request<void>(`/submissions/${id}`, { method: 'DELETE' }),
    },
    attendance: {
      list: (params?: { course_id?: number }) => {
        const query = params ? '?' + new URLSearchParams(params as Record<string, string>).toString() : ''
        return request<AttendanceSession[]>(`/attendance${query}`)
      },
      get: (id: number) => request<AttendanceSession>(`/attendance/${id}`),
      create: (data: Partial<AttendanceSession>) => request<AttendanceSession>('/attendance', { method: 'POST', body: JSON.stringify(data) }),
      update: (id: number, data: Partial<AttendanceSession>) => request<AttendanceSession>(`/attendance/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
      delete: (id: number) => request<void>(`/attendance/${id}`, { method: 'DELETE' }),
    },
  }
}
