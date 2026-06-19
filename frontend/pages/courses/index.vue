<template>
  <div>
    <div class="page-header flex items-center justify-between">
      <div>
        <h1 class="page-title">课程管理</h1>
        <p class="page-subtitle">管理所有课程信息</p>
      </div>
      <button class="btn btn-primary" @click="showCreate = true">+ 新建课程</button>
    </div>

    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>课程名称</th>
            <th>课程代码</th>
            <th>授课教师</th>
            <th>学期</th>
            <th>报名人数</th>
            <th>点名任务</th>
            <th class="text-right">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="courses.length === 0">
            <td colspan="7" class="text-center text-muted">暂无课程数据</td>
          </tr>
          <tr v-for="course in courses" :key="course.id">
            <td class="font-medium">{{ course.name }}</td>
            <td>{{ course.code }}</td>
            <td>{{ course.teacher_name || '-' }}</td>
            <td>{{ course.semester || '-' }}</td>
            <td>{{ course.enrollments_count ?? 0 }}</td>
            <td>{{ course.assignments_count ?? 0 }}</td>
            <td class="text-right">
              <div class="flex gap-2 justify-end">
                <NuxtLink :to="`/courses/${course.id}/roll-call`" class="btn btn-sm btn-success">点名批改</NuxtLink>
                <button class="btn btn-sm btn-outline" @click="editCourse(course)">编辑</button>
                <button class="btn btn-sm btn-danger" @click="deleteCourse(course.id)">删除</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showCreate = false">
      <div class="card w-full max-w-md">
        <div class="card-header">
          <h3 class="font-semibold">{{ editing ? '编辑课程' : '新建课程' }}</h3>
          <button class="text-muted" @click="showCreate = false">✕</button>
        </div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">课程名称 *</label>
            <input v-model="form.name" class="form-input" placeholder="请输入课程名称" />
          </div>
          <div class="form-group">
            <label class="form-label">课程代码 *</label>
            <input v-model="form.code" class="form-input" placeholder="请输入课程代码" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="form-group">
              <label class="form-label">授课教师</label>
              <input v-model="form.teacher_name" class="form-input" placeholder="教师姓名" />
            </div>
            <div class="form-group">
              <label class="form-label">学期</label>
              <input v-model="form.semester" class="form-input" placeholder="如：2026春季" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">课程描述</label>
            <textarea v-model="form.description" class="form-textarea" placeholder="课程描述信息"></textarea>
          </div>
          <div class="flex gap-2 justify-end">
            <button class="btn btn-outline" @click="showCreate = false">取消</button>
            <button class="btn btn-primary" @click="submitCourse">保存</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Course } from '~/types'

const api = useApi()
const courses = ref<Course[]>([])
const showCreate = ref(false)
const editing = ref(false)
const editingId = ref<number | null>(null)

const form = reactive({
  name: '',
  code: '',
  description: '',
  teacher_name: '',
  semester: '',
})

function resetForm() {
  form.name = ''
  form.code = ''
  form.description = ''
  form.teacher_name = ''
  form.semester = ''
  editing.value = false
  editingId.value = null
}

function editCourse(course: Course) {
  form.name = course.name
  form.code = course.code
  form.description = course.description || ''
  form.teacher_name = course.teacher_name || ''
  form.semester = course.semester || ''
  editing.value = true
  editingId.value = course.id
  showCreate.value = true
}

async function loadCourses() {
  try {
    courses.value = await api.courses.list()
  } catch (e) {
    courses.value = [
      { id: 1, name: 'Web前端开发', code: 'CS101', teacher_name: '张老师', semester: '2026春季', enrollments_count: 8, assignments_count: 2 },
      { id: 2, name: '后端开发入门', code: 'CS102', teacher_name: '李老师', semester: '2026春季', enrollments_count: 5, assignments_count: 1 },
    ]
  }
}

async function submitCourse() {
  try {
    if (editing.value && editingId.value) {
      await api.courses.update(editingId.value, form)
    } else {
      await api.courses.create(form)
    }
    showCreate.value = false
    resetForm()
    await loadCourses()
  } catch (e: any) {
    alert(e.message || '保存失败')
  }
}

async function deleteCourse(id: number) {
  if (!confirm('确定要删除此课程吗？')) return
  try {
    await api.courses.delete(id)
    await loadCourses()
  } catch (e: any) {
    alert(e.message || '删除失败')
  }
}

onMounted(loadCourses)
</script>
