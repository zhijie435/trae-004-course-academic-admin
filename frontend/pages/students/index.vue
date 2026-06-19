<template>
  <div>
    <div class="page-header flex items-center justify-between">
      <div>
        <h1 class="page-title">学生管理</h1>
        <p class="page-subtitle">管理学生信息与报名</p>
      </div>
      <button class="btn btn-primary" @click="showCreate = true">+ 新增学生</button>
    </div>

    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>学号</th>
            <th>姓名</th>
            <th>班级</th>
            <th>邮箱</th>
            <th>电话</th>
            <th>报名课程</th>
            <th class="text-right">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="students.length === 0">
            <td colspan="7" class="text-center text-muted">暂无学生数据</td>
          </tr>
          <tr v-for="student in students" :key="student.id">
            <td class="font-mono">{{ student.student_no }}</td>
            <td class="font-medium">{{ student.name }}</td>
            <td>{{ student.class_name || '-' }}</td>
            <td>{{ student.email || '-' }}</td>
            <td>{{ student.phone || '-' }}</td>
            <td>{{ student.enrollments_count ?? 0 }}</td>
            <td class="text-right">
              <div class="flex gap-2 justify-end">
                <button class="btn btn-sm btn-outline" @click="editStudent(student)">编辑</button>
                <button class="btn btn-sm btn-danger" @click="deleteStudent(student.id)">删除</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showCreate = false">
      <div class="card w-full max-w-md">
        <div class="card-header">
          <h3 class="font-semibold">{{ editing ? '编辑学生' : '新增学生' }}</h3>
          <button class="text-muted" @click="showCreate = false">✕</button>
        </div>
        <div class="card-body">
          <div class="grid grid-cols-2 gap-4">
            <div class="form-group">
              <label class="form-label">学号 *</label>
              <input v-model="form.student_no" class="form-input" placeholder="学号" />
            </div>
            <div class="form-group">
              <label class="form-label">姓名 *</label>
              <input v-model="form.name" class="form-input" placeholder="姓名" />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">班级</label>
            <input v-model="form.class_name" class="form-input" placeholder="班级名称" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="form-group">
              <label class="form-label">邮箱</label>
              <input v-model="form.email" type="email" class="form-input" placeholder="邮箱地址" />
            </div>
            <div class="form-group">
              <label class="form-label">电话</label>
              <input v-model="form.phone" class="form-input" placeholder="联系电话" />
            </div>
          </div>
          <div class="flex gap-2 justify-end">
            <button class="btn btn-outline" @click="showCreate = false">取消</button>
            <button class="btn btn-primary" @click="submitStudent">保存</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Student } from '~/types'

const api = useApi()
const students = ref<Student[]>([])
const showCreate = ref(false)
const editing = ref(false)
const editingId = ref<number | null>(null)

const form = reactive({
  name: '',
  student_no: '',
  email: '',
  phone: '',
  class_name: '',
})

function resetForm() {
  form.name = ''
  form.student_no = ''
  form.email = ''
  form.phone = ''
  form.class_name = ''
  editing.value = false
  editingId.value = null
}

function editStudent(s: Student) {
  form.name = s.name
  form.student_no = s.student_no
  form.email = s.email || ''
  form.phone = s.phone || ''
  form.class_name = s.class_name || ''
  editing.value = true
  editingId.value = s.id
  showCreate.value = true
}

async function loadStudents() {
  try {
    students.value = await api.students.list()
  } catch (e) {
    students.value = [
      { id: 1, name: '王小明', student_no: '2024001', class_name: '计算机2401', email: 'xiaoming@example.com', phone: '13800138001', enrollments_count: 2 },
      { id: 2, name: '李小红', student_no: '2024002', class_name: '计算机2401', email: 'xiaohong@example.com', phone: '13800138002', enrollments_count: 2 },
      { id: 3, name: '张三', student_no: '2024003', class_name: '计算机2401', email: 'zhangsan@example.com', phone: '13800138003', enrollments_count: 2 },
      { id: 4, name: '李四', student_no: '2024004', class_name: '计算机2401', email: 'lisi@example.com', phone: '13800138004', enrollments_count: 2 },
      { id: 5, name: '王五', student_no: '2024005', class_name: '计算机2402', email: 'wangwu@example.com', phone: '13800138005', enrollments_count: 2 },
    ]
  }
}

async function submitStudent() {
  try {
    if (editing.value && editingId.value) {
      await api.students.update(editingId.value, form)
    } else {
      await api.students.create(form)
    }
    showCreate.value = false
    resetForm()
    await loadStudents()
  } catch (e: any) {
    alert(e.message || '保存失败')
  }
}

async function deleteStudent(id: number) {
  if (!confirm('确定要删除此学生吗？')) return
  try {
    await api.students.delete(id)
    await loadStudents()
  } catch (e: any) {
    alert(e.message || '删除失败')
  }
}

onMounted(loadStudents)
</script>
