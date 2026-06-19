<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">在线课程教务系统</h1>
      <p class="page-subtitle">管理课程、学生报名与课堂点名批改</p>
    </div>

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
  </div>
</template>

<script setup lang="ts">
const api = useApi()

const stats = reactive({
  courses: 0,
  students: 0,
  enrollments: 0,
  rollCalls: 0,
})

const recentAssignments = ref<any[]>([])

onMounted(async () => {
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
    // Use demo data if API fails
    stats.courses = 2
    stats.students = 8
    stats.enrollments = 13
    stats.rollCalls = 3
  }
})
</script>
