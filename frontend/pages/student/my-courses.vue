<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">我的课程</h1>
      <p class="page-subtitle">
        {{ appState.state.currentStudent?.name }}（{{ appState.state.currentStudent?.student_no }}）已报名的课程
      </p>
    </div>

    <div v-if="loading" class="text-center text-muted py-12">加载中...</div>

    <div v-else-if="myCourses.length === 0" class="empty-state">
      <div class="empty-state-icon">📭</div>
      <div class="mb-4">你还没有报名任何课程</div>
      <NuxtLink to="/student/courses" class="btn btn-primary">去报名课程</NuxtLink>
    </div>

    <div v-else class="grid grid-cols-2 gap-4">
      <div v-for="item in myCourses" :key="item.course.id" class="card">
        <div class="card-header">
          <div>
            <h3 class="font-semibold text-lg">{{ item.course.name }}</h3>
            <div class="text-sm text-muted mt-1">
              <span class="font-mono">{{ item.course.code }}</span>
              <span v-if="item.course.teacher_name" class="ml-3">讲师：{{ item.course.teacher_name }}</span>
            </div>
          </div>
          <span class="badge badge-success">已报名</span>
        </div>
        <div class="card-body">
          <p class="text-sm text-muted mb-4">{{ item.course.description || '暂无课程描述' }}</p>

          <div class="mb-3">
            <div class="text-sm font-medium mb-2">点名任务（{{ item.assignments.length }}）</div>
            <div v-if="item.assignments.length === 0" class="text-sm text-muted">暂无点名任务</div>
            <div v-else class="space-y-2">
              <div
                v-for="a in item.assignments"
                :key="a.id"
                class="flex items-center justify-between p-2 rounded bg-gray-50"
              >
                <div>
                  <div class="text-sm font-medium">{{ a.title }}</div>
                  <div class="text-xs text-muted">{{ formatDate(a.created_at) }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <span v-if="getMySubmission(a.id)" :class="submissionBadgeClass(getMySubmission(a.id)!.status)">
                    {{ submissionText(getMySubmission(a.id)!.status) }}
                    <span v-if="getMySubmission(a.id)!.score !== null && getMySubmission(a.id)!.score !== undefined">
                      · {{ getMySubmission(a.id)!.score }}分
                    </span>
                  </span>
                  <span v-else class="badge badge-warning">待签到</span>
                  <button
                    v-if="!getMySubmission(a.id)"
                    class="btn btn-sm btn-primary"
                    @click="goSignIn(item.course.id, a.id)"
                  >
                    去签到
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="flex gap-2 justify-end">
            <NuxtLink :to="`/student/sign-in?course_id=${item.course.id}`" class="btn btn-sm btn-outline">
              查看签到
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Course, Assignment, Submission, Enrollment } from '~/types'

const api = useApi()
const appState = useAppState()

interface CourseWithData {
  course: Course
  enrollments_count?: number
  assignments: Assignment[]
}

const myCourses = ref<CourseWithData[]>([])
const mySubmissions = ref<Submission[]>([])
const loading = ref(true)

function formatDate(dateStr?: string) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('zh-CN')
}

function getMySubmission(assignmentId: number) {
  return mySubmissions.value.find(s => s.assignment_id === assignmentId)
}

function submissionBadgeClass(status: string) {
  switch (status) {
    case 'graded': return 'badge badge-success'
    case 'absent': return 'badge badge-danger'
    case 'pending': return 'badge badge-warning'
    default: return 'badge badge-secondary'
  }
}

function submissionText(status: string) {
  switch (status) {
    case 'graded': return '已批改'
    case 'absent': return '缺勤'
    case 'pending': return '待批改'
    default: return status
  }
}

function goSignIn(courseId: number, assignmentId: number) {
  navigateTo(`/student/sign-in?course_id=${courseId}&assignment_id=${assignmentId}`)
}

async function loadData() {
  loading.value = true
  const studentId = appState.state.currentStudent?.id
  if (!studentId) {
    loading.value = false
    return
  }

  try {
    const [enrollments, submissions] = await Promise.all([
      api.enrollments.list({ student_id: studentId }),
      api.submissions.list({ student_id: studentId }),
    ])
    mySubmissions.value = submissions

    const allCourses = await api.courses.list()
    const myCourseIds = enrollments.map(e => e.course_id)

    const result: CourseWithData[] = []
    for (const cid of myCourseIds) {
      const course = allCourses.find(c => c.id === cid)
      if (!course) continue
      try {
        const assignments = await api.assignments.list({ course_id: cid, type: 'roll_call' })
        result.push({ course, assignments })
      } catch (e) {
        result.push({ course, assignments: [] })
      }
    }
    myCourses.value = result
  } catch (e) {
    const sid = studentId
    const demoEnrollments = sid <= 5
      ? [{ course_id: 1 }, { course_id: 2 }]
      : [{ course_id: 1 }]

    const demoCourses = [
      { id: 1, name: 'Web前端开发', code: 'CS101', description: '学习HTML、CSS、JavaScript和Vue框架', teacher_name: '张老师', semester: '2026春季' },
      { id: 2, name: '后端开发入门', code: 'CS102', description: '学习PHP和Laravel框架', teacher_name: '李老师', semester: '2026春季' },
    ]

    mySubmissions.value = [
      { id: sid, assignment_id: 1, student_id: sid, content: '已到课', score: sid <= 3 ? 100 : (sid === 4 ? 80 : null), feedback: sid <= 4 ? (sid === 4 ? '迟到10分钟' : '出勤正常') : null, status: sid <= 4 ? 'graded' : 'pending', submitted_at: '2026-06-19T09:00:00' } as Submission,
    ]

    myCourses.value = demoEnrollments.map((en, idx) => {
      const course = demoCourses.find(c => c.id === en.course_id)!
      return {
        course,
        assignments: idx === 0
          ? [
              { id: 1, course_id: 1, title: '第1次课堂点名', type: 'roll_call', max_score: 100, is_published: true, created_at: '2026-06-19T09:00:00' } as Assignment,
              { id: 2, course_id: 1, title: '第2次课堂点名', type: 'roll_call', max_score: 100, is_published: true, created_at: '2026-06-20T09:00:00' } as Assignment,
            ]
          : [
              { id: 3, course_id: 2, title: '第1次课堂点名', type: 'roll_call', max_score: 100, is_published: true, created_at: '2026-06-19T09:00:00' } as Assignment,
            ],
      }
    })
  } finally {
    loading.value = false
  }
}

onMounted(loadData)
</script>
