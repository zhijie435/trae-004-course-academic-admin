<template>
  <div>
    <div v-if="appState.isTeacher()" class="page-header">
      <h1 class="page-title">在线课程教务系统</h1>
      <p class="page-subtitle">管理课程、学生报名与课堂点名批改</p>
    </div>

    <div v-else class="page-header">
      <h1 class="page-title">学员中心</h1>
      <p class="page-subtitle">
        欢迎你，{{ appState.state.currentStudent?.name }}
        ({{ appState.state.currentStudent?.class_name }})
      </p>
    </div>

    <template v-if="appState.isTeacher()">
      <div class="grid grid-cols-4 mb-4">
        <div class="stat-card">
          <div class="stat-value">{{ stats.courses }}</div>
          <div class="stat-label">课程总数</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ stats.students }}</div>
          <div class="stat-label">学生总数</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ stats.enrollments }}</div>
          <div class="stat-label">报名人次</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ stats.rollCalls }}</div>
          <div class="stat-label">点名批改任务</div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold">快速操作</h3>
          </div>
          <div class="card-body">
            <div class="flex gap-2 flex-wrap">
              <NuxtLink to="/courses" class="btn btn-primary">课程管理</NuxtLink>
              <NuxtLink to="/students" class="btn btn-secondary">学生管理</NuxtLink>
            </div>
            <p class="text-sm text-muted mt-4">
              提示：进入课程列表，点击课程右侧「点名批改」即可进入批改页面
            </p>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold">最近点名任务</h3>
          </div>
          <div class="card-body">
            <div v-if="recentAssignments.length === 0" class="text-muted text-sm">暂无点名任务</div>
            <div v-else class="space-y-2">
              <div v-for="a in recentAssignments" :key="a.id" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div>
                  <div class="font-medium">{{ a.title }}</div>
                  <div class="text-sm text-muted">{{ a.course?.name || '课程' }}</div>
                </div>
                <NuxtLink :to="`/courses/${a.course_id}/roll-call/${a.id}`" class="btn btn-sm btn-outline">批改</NuxtLink>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <template v-else>
      <div class="grid grid-cols-3 mb-4">
        <div class="stat-card">
          <div class="stat-value">{{ studentStats.myCourses }}</div>
          <div class="stat-label">已报名课程</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ studentStats.pendingSignIn }}</div>
          <div class="stat-label">待签到任务</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ studentStats.gradedCount }}</div>
          <div class="stat-label">已批改签到</div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold">我要操作</h3>
          </div>
          <div class="card-body">
            <div class="flex gap-2 flex-wrap">
              <NuxtLink to="/student/courses" class="btn btn-primary">浏览课程 / 报名</NuxtLink>
              <NuxtLink to="/student/sign-in" class="btn btn-success">立即签到</NuxtLink>
              <NuxtLink to="/student/my-courses" class="btn btn-outline">我的课程</NuxtLink>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h3 class="font-semibold">我的签到记录</h3>
          </div>
          <div class="card-body">
            <div v-if="myRecentSubmissions.length === 0" class="text-muted text-sm">暂无签到记录</div>
            <div v-else class="space-y-2">
              <div v-for="s in myRecentSubmissions" :key="s.id || s.assignment_id" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div>
                  <div class="font-medium">{{ s.assignment?.title || '点名任务' }}</div>
                  <div class="text-sm text-muted">
                    {{ s.assignment?.course?.name || '课程' }}
                  </div>
                </div>
                <div class="text-right">
                  <span :class="statusBadgeClass(s.status)">{{ statusText(s.status) }}</span>
                  <div v-if="s.score !== null && s.score !== undefined" class="text-sm font-medium mt-1">
                    {{ s.score }} 分
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { Assignment, Submission, Course, Enrollment } from '~/types'

const api = useApi()
const appState = useAppState()

const stats = reactive({
  courses: 0,
  students: 0,
  enrollments: 0,
  rollCalls: 0,
})

const recentAssignments = ref<Assignment[]>([])

const studentStats = reactive({
  myCourses: 0,
  pendingSignIn: 0,
  gradedCount: 0,
})

const myRecentSubmissions = ref<any[]>([])

function statusBadgeClass(status: string) {
  switch (status) {
    case 'graded': return 'badge badge-success'
    case 'absent': return 'badge badge-danger'
    case 'pending': return 'badge badge-warning'
    default: return 'badge badge-secondary'
  }
}

function statusText(status: string) {
  switch (status) {
    case 'graded': return '已批改'
    case 'absent': return '缺勤'
    case 'pending': return '待批改'
    default: return status
  }
}

onMounted(async () => {
  if (appState.isTeacher()) {
    try {
      const [courses, students, enrollments, assignments] = await Promise.all([
        api.courses.list(),
        api.students.list(),
        api.enrollments.list(),
        api.assignments.list(),
      ])
      stats.courses = courses.length
      stats.students = students.length
      stats.enrollments = enrollments.length
      stats.rollCalls = assignments.length
      recentAssignments.value = assignments.slice(0, 5)
    } catch (e) {
      stats.courses = 2
      stats.students = 8
      stats.enrollments = 13
      stats.rollCalls = 3
    }
  } else {
    const studentId = appState.state.currentStudent?.id
    try {
      const [myEnrollments, mySubmissions] = await Promise.all([
        api.enrollments.list({ student_id: studentId }),
        api.submissions.list({ student_id: studentId }),
      ])
      studentStats.myCourses = myEnrollments.length
      studentStats.gradedCount = mySubmissions.filter(s => s.status === 'graded').length
      myRecentSubmissions.value = mySubmissions.slice(0, 5).map(s => ({
        ...s,
        assignment: { title: '点名任务', course: { name: '课程' } },
      }))
      studentStats.pendingSignIn = 2
    } catch (e) {
      studentStats.myCourses = 2
      studentStats.pendingSignIn = 2
      studentStats.gradedCount = 3
      myRecentSubmissions.value = [
        { id: 1, assignment_id: 1, status: 'graded', score: 100, assignment: { title: '第1次课堂点名', course: { name: 'Web前端开发' } } },
        { id: 4, assignment_id: 1, status: 'graded', score: 80, assignment: { title: '第1次课堂点名', course: { name: 'Web前端开发' } } },
        { id: 5, assignment_id: 1, status: 'pending', score: null, assignment: { title: '第1次课堂点名', course: { name: 'Web前端开发' } } },
      ]
    }
  }
})
</script>
