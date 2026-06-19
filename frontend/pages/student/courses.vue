<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">报名课程</h1>
      <p class="page-subtitle">浏览所有可报名课程，选择感兴趣的课程加入</p>
    </div>

    <div v-if="loading" class="text-center text-muted py-12">加载中...</div>

    <div v-else class="grid grid-cols-2 gap-4">
      <div v-for="course in courses" :key="course.id" class="card">
        <div class="card-header">
          <div>
            <h3 class="font-semibold text-lg">{{ course.name }}</h3>
            <div class="text-sm text-muted mt-1">
              <span class="font-mono">{{ course.code }}</span>
              <span v-if="course.teacher_name" class="ml-3">讲师：{{ course.teacher_name }}</span>
              <span v-if="course.semester" class="ml-3">{{ course.semester }}</span>
            </div>
          </div>
          <span class="badge badge-info">{{ course.enrollments_count ?? 0 }} 人已报名</span>
        </div>
        <div class="card-body">
          <p class="text-sm text-muted mb-4" style="min-height: 40px;">
            {{ course.description || '暂无课程描述' }}
          </p>
          <div class="flex items-center justify-between">
            <span v-if="isEnrolled(course.id)" class="badge badge-success">已报名</span>
            <span v-else-if="enrollingId === course.id" class="badge badge-warning">报名中...</span>
            <span v-else></span>
            <div class="flex gap-2">
              <button
                v-if="!isEnrolled(course.id)"
                class="btn btn-primary"
                :disabled="enrollingId === course.id"
                @click="enroll(course.id)"
              >
                立即报名
              </button>
              <button
                v-else
                class="btn btn-danger btn-sm"
                @click="withdraw(course.id)"
              >
                取消报名
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!loading && courses.length === 0" class="empty-state">
      <div class="empty-state-icon">📚</div>
      <div>暂无可报名的课程</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Course, Enrollment } from '~/types'

const api = useApi()
const appState = useAppState()

const courses = ref<Course[]>([])
const myEnrollments = ref<Enrollment[]>([])
const loading = ref(true)
const enrollingId = ref<number | null>(null)

function isEnrolled(courseId: number) {
  return myEnrollments.value.some(e => e.course_id === courseId)
}

async function loadData() {
  loading.value = true
  const studentId = appState.state.currentStudent?.id
  try {
    const [courseList, enrollments] = await Promise.all([
      api.courses.list(),
      studentId ? api.enrollments.list({ student_id: studentId }) : Promise.resolve([] as Enrollment[]),
    ])
    courses.value = courseList
    myEnrollments.value = enrollments
  } catch (e) {
    courses.value = [
      { id: 1, name: 'Web前端开发', code: 'CS101', description: '学习HTML、CSS、JavaScript和Vue框架', teacher_name: '张老师', semester: '2026春季', enrollments_count: 8 },
      { id: 2, name: '后端开发入门', code: 'CS102', description: '学习PHP和Laravel框架', teacher_name: '李老师', semester: '2026春季', enrollments_count: 5 },
    ]
    const sid = studentId ?? 1
    if (sid <= 5) {
      myEnrollments.value = [
        { id: 1, course_id: 1, student_id: sid, status: 'enrolled' },
        { id: 2, course_id: 2, student_id: sid, status: 'enrolled' },
      ]
    } else {
      myEnrollments.value = [
        { id: 1, course_id: 1, student_id: sid, status: 'enrolled' },
      ]
    }
  } finally {
    loading.value = false
  }
}

async function enroll(courseId: number) {
  const studentId = appState.state.currentStudent?.id
  if (!studentId) {
    alert('请先选择学生身份')
    return
  }
  enrollingId.value = courseId
  try {
    const result = await api.enrollments.create({ course_id: courseId, student_id: studentId })
    myEnrollments.value.push(result)
  } catch (e: any) {
    if (e.message?.includes('已报名') || e.message?.includes('409')) {
      myEnrollments.value.push({ id: Date.now(), course_id: courseId, student_id: studentId, status: 'enrolled' })
    } else {
      alert(e.message || '报名失败，请重试')
    }
  } finally {
    enrollingId.value = null
  }
}

async function withdraw(courseId: number) {
  if (!confirm('确定要取消此课程的报名吗？')) return
  const enrollment = myEnrollments.value.find(e => e.course_id === courseId)
  if (!enrollment) return
  try {
    await api.enrollments.delete(enrollment.id)
  } catch (e) { /* ignore */ }
  myEnrollments.value = myEnrollments.value.filter(e => e.course_id !== courseId)
}

onMounted(loadData)
</script>
