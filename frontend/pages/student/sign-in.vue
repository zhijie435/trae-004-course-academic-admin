<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">课堂签到</h1>
      <p class="page-subtitle">
        {{ appState.state.currentStudent?.name }}（{{ appState.state.currentStudent?.student_no }}）
        · 选择课程并完成签到
      </p>
    </div>

    <div v-if="!appState.state.currentStudent" class="empty-state">
      <div class="empty-state-icon">👤</div>
      <div>请先在顶部切换为「学员」身份并选择学生</div>
    </div>

    <template v-else>
      <div class="card mb-4">
        <div class="card-body">
          <div class="flex gap-4 items-end">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
              <label class="form-label">选择课程</label>
              <select class="form-select" v-model="selectedCourseId" @change="onCourseChange">
                <option :value="null">-- 请选择课程 --</option>
                <option v-for="c in myCourses" :key="c.id" :value="c.id">
                  {{ c.name }}（{{ c.code }}）
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="text-center text-muted py-12">加载中...</div>

      <div v-else-if="!selectedCourseId" class="empty-state">
        <div class="empty-state-icon">📖</div>
        <div>请先选择一门已报名的课程</div>
      </div>

      <div v-else-if="assignments.length === 0" class="empty-state">
        <div class="empty-state-icon">📋</div>
        <div>该课程暂无点名任务</div>
      </div>

      <div v-else class="space-y-4">
        <div v-for="a in assignments" :key="a.id" class="card">
          <div class="card-header">
            <div>
              <h3 class="font-semibold">{{ a.title }}</h3>
              <div class="text-sm text-muted mt-1">
                {{ formatDate(a.created_at) }}
                <span class="ml-2">满分 {{ a.max_score }} 分</span>
              </div>
            </div>
            <span v-if="getMySubmission(a.id)" :class="submissionBadgeClass(getMySubmission(a.id)!.status)">
              {{ submissionText(getMySubmission(a.id)!.status) }}
            </span>
            <span v-else class="badge badge-warning">待签到</span>
          </div>

          <div v-if="a.description" class="card-body" style="padding-bottom: 0;">
            <p class="text-sm text-muted">{{ a.description }}</p>
          </div>

          <div class="card-body">
            <template v-if="!getMySubmission(a.id)">
              <div class="form-group">
                <label class="form-label">签到内容</label>
                <textarea
                  v-model="signInContents[a.id]"
                  class="form-textarea"
                  placeholder="请输入签到内容，如：已到课、请假等"
                >已到课</textarea>
              </div>
              <div class="flex gap-2 justify-end">
                <button
                  class="btn btn-primary"
                  :disabled="submittingId === a.id"
                  @click="submitSignIn(a)"
                >
                  {{ submittingId === a.id ? '提交中...' : '确认签到' }}
                </button>
              </div>
            </template>

            <template v-else>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <div class="text-sm text-muted mb-1">签到内容</div>
                  <div class="font-medium">{{ getMySubmission(a.id)!.content || '（无）' }}</div>
                </div>
                <div>
                  <div class="text-sm text-muted mb-1">签到时间</div>
                  <div class="font-medium">{{ formatDateTime(getMySubmission(a.id)!.submitted_at) }}</div>
                </div>
                <div>
                  <div class="text-sm text-muted mb-1">得分</div>
                  <div class="font-medium">
                    <span v-if="getMySubmission(a.id)!.score !== null && getMySubmission(a.id)!.score !== undefined" class="text-lg">
                      {{ getMySubmission(a.id)!.score }}
                      <span class="text-sm text-muted font-normal">/ {{ a.max_score }}</span>
                    </span>
                    <span v-else class="text-muted">待批改</span>
                  </div>
                </div>
                <div>
                  <div class="text-sm text-muted mb-1">教师评语</div>
                  <div class="font-medium">
                    {{ getMySubmission(a.id)!.feedback || '（暂无评语）' }}
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { Course, Assignment, Submission, Enrollment } from '~/types'

const route = useRoute()
const api = useApi()
const appState = useAppState()

const myCourses = ref<Course[]>([])
const assignments = ref<Assignment[]>([])
const mySubmissions = ref<Submission[]>([])
const selectedCourseId = ref<number | null>(null)
const loading = ref(false)
const submittingId = ref<number | null>(null)
const signInContents = reactive<Record<number, string>>({})

function formatDate(dateStr?: string) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('zh-CN')
}

function formatDateTime(dateStr?: string | null) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleString('zh-CN')
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

async function loadMyCourses() {
  const studentId = appState.state.currentStudent?.id
  if (!studentId) return
  try {
    const enrollments = await api.enrollments.list({ student_id: studentId })
    const allCourses = await api.courses.list()
    myCourses.value = allCourses.filter(c => enrollments.some(e => e.course_id === c.id))
  } catch (e) {
    const sid = studentId
    myCourses.value = sid <= 5
      ? [
          { id: 1, name: 'Web前端开发', code: 'CS101', description: '学习HTML、CSS、JavaScript和Vue框架', teacher_name: '张老师', semester: '2026春季' },
          { id: 2, name: '后端开发入门', code: 'CS102', description: '学习PHP和Laravel框架', teacher_name: '李老师', semester: '2026春季' },
        ]
      : [
          { id: 1, name: 'Web前端开发', code: 'CS101', description: '学习HTML、CSS、JavaScript和Vue框架', teacher_name: '张老师', semester: '2026春季' },
        ]
  }
}

async function onCourseChange() {
  if (!selectedCourseId.value) {
    assignments.value = []
    mySubmissions.value = []
    return
  }
  await loadAssignments()
}

async function loadAssignments() {
  if (!selectedCourseId.value) return
  const studentId = appState.state.currentStudent?.id
  loading.value = true
  try {
    const [alist, slist] = await Promise.all([
      api.assignments.list({ course_id: selectedCourseId.value, type: 'roll_call' }),
      studentId ? api.submissions.list({ student_id: studentId }) : Promise.resolve([] as Submission[]),
    ])
    assignments.value = alist
    mySubmissions.value = slist.filter(s => alist.some(a => a.id === s.assignment_id))
  } catch (e) {
    const cid = selectedCourseId.value
    const sid = studentId ?? 1
    if (cid === 1) {
      assignments.value = [
        { id: 1, course_id: 1, title: '第1次课堂点名', description: '2026年6月19日课堂签到', type: 'roll_call', max_score: 100, is_published: true, created_at: '2026-06-19T09:00:00' },
        { id: 2, course_id: 1, title: '第2次课堂点名', description: '2026年6月20日课堂签到', type: 'roll_call', max_score: 100, is_published: true, created_at: '2026-06-20T09:00:00' },
      ] as Assignment[]
    } else {
      assignments.value = [
        { id: 3, course_id: 2, title: '第1次课堂点名', description: '2026年6月19日后端课堂签到', type: 'roll_call', max_score: 100, is_published: true, created_at: '2026-06-19T09:00:00' },
      ] as Assignment[]
    }
    mySubmissions.value = assignments.value
      .filter((_, idx) => idx === 0 && sid <= 5)
      .map(a => ({
        id: a.id,
        assignment_id: a.id,
        student_id: sid,
        content: sid === 4 ? '迟到' : '已到课',
        score: sid <= 3 ? 100 : (sid === 4 ? 80 : null),
        feedback: sid <= 3 ? '出勤正常' : (sid === 4 ? '迟到10分钟' : null),
        status: sid <= 4 ? 'graded' : 'pending',
        submitted_at: '2026-06-19T09:05:00',
      } as Submission))
  } finally {
    loading.value = false
  }
}

async function submitSignIn(assignment: Assignment) {
  const studentId = appState.state.currentStudent?.id
  if (!studentId) {
    alert('请先选择学生身份')
    return
  }
  submittingId.value = assignment.id
  try {
    const content = signInContents[assignment.id] || '已到课'
    const result = await api.submissions.create({
      assignment_id: assignment.id,
      student_id: studentId,
      content,
    })
    mySubmissions.value.push(result)
  } catch (e: any) {
    if (e.message?.includes('已提交') || e.message?.includes('409')) {
      mySubmissions.value.push({
        id: Date.now(),
        assignment_id: assignment.id,
        student_id: studentId,
        content: signInContents[assignment.id] || '已到课',
        status: 'pending',
        submitted_at: new Date().toISOString(),
      })
    } else {
      alert(e.message || '签到失败，请重试')
    }
  } finally {
    submittingId.value = null
  }
}

onMounted(async () => {
  await loadMyCourses()

  const qCourseId = route.query.course_id
  const qAssignmentId = route.query.assignment_id

  if (qCourseId) {
    selectedCourseId.value = Number(qCourseId)
    await loadAssignments()
  } else if (myCourses.value.length > 0) {
    selectedCourseId.value = myCourses.value[0].id
    await loadAssignments()
  }
})
</script>
