<template>
  <div>
    <div class="page-header">
      <NuxtLink to="/courses" class="text-sm text-muted mb-2 inline-block">← 返回课程列表</NuxtLink>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="page-title">{{ course?.name || '课程点名批改' }}</h1>
          <p class="page-subtitle">
            {{ course?.code ? course.code + ' · ' : '' }}
            {{ course?.teacher_name || '' }}
            {{ course?.semester ? ' · ' + course.semester : '' }}
          </p>
        </div>
        <button class="btn btn-primary" @click="showCreate = true">+ 新建点名</button>
      </div>
    </div>

    <div class="grid grid-cols-3 mb-4">
      <div class="stat-card">
        <div class="stat-value">{{ stats.totalStudents }}</div>
        <div class="stat-label">报名学生数</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ assignments.length }}</div>
        <div class="stat-label">点名任务数</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.gradedCount }}</div>
        <div class="stat-label">已批改总人次</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="font-semibold">点名任务列表</h3>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th>点名标题</th>
            <th>创建时间</th>
            <th>满分</th>
            <th>已提交</th>
            <th>已批改</th>
            <th>未提交</th>
            <th class="text-right">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="assignments.length === 0">
            <td colspan="7" class="text-center text-muted py-8">
              暂无点名任务，点击右上角「新建点名」开始
            </td>
          </tr>
          <tr v-for="a in assignments" :key="a.id">
            <td class="font-medium">{{ a.title }}</td>
            <td class="text-sm text-muted">{{ formatDate(a.created_at) }}</td>
            <td>{{ a.max_score }} 分</td>
            <td>
              <span class="badge badge-info">{{ a.stats?.submitted ?? a.submissions_count ?? 0 }}</span>
            </td>
            <td>
              <span class="badge badge-success">{{ a.stats?.graded ?? 0 }}</span>
            </td>
            <td>
              <span class="badge badge-danger">{{ a.stats?.missing ?? 0 }}</span>
            </td>
            <td class="text-right">
              <div class="flex gap-2 justify-end">
                <NuxtLink :to="`/courses/${courseId}/roll-call/${a.id}`" class="btn btn-sm btn-primary">
                  批改详情
                </NuxtLink>
                <button class="btn btn-sm btn-outline" @click="editAssignment(a)">编辑</button>
                <button class="btn btn-sm btn-danger" @click="deleteAssignment(a.id)">删除</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showCreate = false">
      <div class="card w-full max-w-md">
        <div class="card-header">
          <h3 class="font-semibold">{{ editing ? '编辑点名' : '新建点名任务' }}</h3>
          <button class="text-muted" @click="showCreate = false">✕</button>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">点名标题 *</label>
            <input v-model="form.title" class="form-input" placeholder="如：第1次课堂点名" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="form-group">
              <label class="form-label">满分</label>
              <input v-model.number="form.max_score" type="number" min="0" class="form-input" placeholder="默认 100" />
            </div>
            <div class="form-group">
              <label class="form-label">截止日期</label>
              <input v-model="form.due_date" type="datetime-local" class="form-input" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">描述说明</label>
            <textarea v-model="form.description" class="form-textarea" placeholder="选填，描述本次点名的说明"></textarea>
          </div>
          <div class="flex gap-2 justify-end">
            <button class="btn btn-outline" @click="showCreate = false">取消</button>
            <button class="btn btn-primary" @click="submitAssignment">保存</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Course, Assignment } from '~/types'

const route = useRoute()
const api = useApi()

const courseId = computed(() => Number(route.params.id))
const course = ref<Course | null>(null)
const assignments = ref<Assignment[]>([])
const showCreate = ref(false)
const editing = ref(false)
const editingId = ref<number | null>(null)

const stats = reactive({
  totalStudents: 0,
  gradedCount: 0,
})

const form = reactive({
  title: '',
  description: '',
  max_score: 100,
  due_date: '',
})

function resetForm() {
  form.title = ''
  form.description = ''
  form.max_score = 100
  form.due_date = ''
  editing.value = false
  editingId.value = null
}

function formatDate(dateStr?: string) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleString('zh-CN', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function editAssignment(a: Assignment) {
  form.title = a.title
  form.description = a.description || ''
  form.max_score = a.max_score
  form.due_date = a.due_date ? new Date(a.due_date).toISOString().slice(0, 16) : ''
  editing.value = true
  editingId.value = a.id
  showCreate.value = true
}

async function loadCourse() {
  try {
    course.value = await api.courses.get(courseId.value)
    stats.totalStudents = course.value.enrollments?.length ?? 0
  } catch (e) {
    course.value = {
      id: courseId.value,
      name: 'Web前端开发',
      code: 'CS101',
      teacher_name: '张老师',
      semester: '2026春季',
    }
    stats.totalStudents = 8
  }
}

async function loadAssignments() {
  try {
    const data = await api.assignments.list({ course_id: courseId.value, type: 'roll_call' })
    assignments.value = data

    for (const a of assignments.value) {
      try {
        const detail = await api.assignments.get(a.id)
        a.stats = detail.stats
      } catch (e) { /* ignore */ }
    }

    stats.gradedCount = assignments.value.reduce((sum, a) => sum + (a.stats?.graded ?? 0), 0)
  } catch (e) {
    assignments.value = [
      {
        id: 1,
        course_id: courseId.value,
        title: '第1次课堂点名',
        description: '2026年6月19日课堂签到',
        type: 'roll_call',
        max_score: 100,
        is_published: true,
        created_at: '2026-06-19T09:00:00',
        stats: { total_enrolled: 8, submitted: 5, missing: 3, graded: 4 },
      },
      {
        id: 2,
        course_id: courseId.value,
        title: '第2次课堂点名',
        description: '2026年6月20日课堂签到',
        type: 'roll_call',
        max_score: 100,
        is_published: true,
        created_at: '2026-06-20T09:00:00',
        stats: { total_enrolled: 8, submitted: 0, missing: 8, graded: 0 },
      },
    ]
    stats.gradedCount = 4
  }
}

async function submitAssignment() {
  try {
    const payload: any = {
      course_id: courseId.value,
      title: form.title,
      description: form.description,
      type: 'roll_call',
      max_score: form.max_score,
      is_published: true,
    }
    if (form.due_date) payload.due_date = form.due_date

    if (editing.value && editingId.value) {
      await api.assignments.update(editingId.value, payload)
    } else {
      await api.assignments.create(payload)
    }
    showCreate.value = false
    resetForm()
    await loadAssignments()
  } catch (e: any) {
    alert(e.message || '保存失败')
    showCreate.value = false
    resetForm()
    await loadAssignments()
  }
}

async function deleteAssignment(id: number) {
  if (!confirm('确定要删除此点名任务吗？相关提交记录也会被删除。')) return
  try {
    await api.assignments.delete(id)
  } catch (e) { /* ignore */ }
  await loadAssignments()
}

onMounted(async () => {
  await loadCourse()
  await loadAssignments()
})
</script>
