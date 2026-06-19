<template>
  <div v-if="assignment">
    <div class="page-header">
      <h1 class="page-title">{{ assignment.title }}</h1>
      <p class="page-subtitle">{{ assignment.course?.name || '作业详情' }} · 满分 {{ assignment.max_score }} 分</p>
    </div>

    <div class="grid grid-cols-4 mb-4">
      <div class="stat-card">
        <div class="stat-value">{{ assignment.stats?.total_enrolled ?? 0 }}</div>
        <div class="stat-label">报名人数</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ assignment.stats?.submitted ?? 0 }}</div>
        <div class="stat-label">已提交</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ assignment.stats?.graded ?? 0 }}</div>
        <div class="stat-label">已批改</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ assignment.stats?.missing ?? 0 }}</div>
        <div class="stat-label">未提交</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="font-semibold">学生提交与批改</h3>
        <div class="flex gap-2">
          <button class="btn btn-sm btn-outline" @click="markAllPresent">一键全勤(满分)</button>
          <button class="btn btn-sm btn-danger" @click="markAllAbsent">一键标记缺勤</button>
        </div>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th>学号</th>
            <th>姓名</th>
            <th>班级</th>
            <th>提交内容</th>
            <th>状态</th>
            <th style="width: 120px">分数</th>
            <th>评语</th>
            <th class="text-right">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="sub in assignment.submissions" :key="sub.student_id">
            <td class="font-mono">{{ sub.student?.student_no }}</td>
            <td class="font-medium">{{ sub.student?.name }}</td>
            <td>{{ sub.student?.class_name || '-' }}</td>
            <td>{{ sub.content || '-' }}</td>
            <td>
              <span :class="statusBadgeClass(sub.status)">
                {{ statusText(sub.status) }}
              </span>
            </td>
            <td>
              <input
                type="number"
                :min="0"
                :max="assignment.max_score"
                class="form-input"
                v-model.number="sub.score"
                :placeholder="'0-' + assignment.max_score"
                @change="updateSubmission(sub)"
              />
            </td>
            <td>
              <input
                type="text"
                class="form-input"
                v-model="sub.feedback"
                placeholder="评语"
                @change="updateSubmission(sub)"
              />
            </td>
            <td class="text-right">
              <div class="flex gap-2 justify-end">
                <button class="btn btn-sm btn-success" @click="gradePresent(sub)">出勤</button>
                <button class="btn btn-sm btn-danger" @click="gradeAbsent(sub)">缺勤</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div v-else class="text-center text-muted py-12">加载中...</div>
</template>

<script setup lang="ts">
import type { Assignment, Submission } from '~/types'

const route = useRoute()
const api = useApi()

const assignment = ref<Assignment | null>(null)
const assignmentId = computed(() => Number(route.params.assignmentId))

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

async function loadAssignment() {
  try {
    assignment.value = await api.assignments.get(assignmentId.value)
  } catch (e) {
    assignment.value = {
      id: assignmentId.value,
      course_id: 1,
      title: '第1次课堂点名',
      description: '2026年6月19日课堂签到',
      type: 'roll_call',
      max_score: 100,
      is_published: true,
      stats: { total_enrolled: 8, submitted: 5, missing: 3, graded: 4 },
      course: { id: 1, name: 'Web前端开发', code: 'CS101' } as any,
      submissions: [
        { id: 1, assignment_id: assignmentId.value, student_id: 1, content: '已到课', score: 100, feedback: '出勤正常', status: 'graded', student: { id: 1, name: '王小明', student_no: '2024001', class_name: '计算机2401' } as any },
        { id: 2, assignment_id: assignmentId.value, student_id: 2, content: '已到课', score: 100, feedback: '出勤正常', status: 'graded', student: { id: 2, name: '李小红', student_no: '2024002', class_name: '计算机2401' } as any },
        { id: 3, assignment_id: assignmentId.value, student_id: 3, content: '已到课', score: 100, feedback: '出勤正常', status: 'graded', student: { id: 3, name: '张三', student_no: '2024003', class_name: '计算机2401' } as any },
        { id: 4, assignment_id: assignmentId.value, student_id: 4, content: '迟到', score: 80, feedback: '迟到10分钟', status: 'graded', student: { id: 4, name: '李四', student_no: '2024004', class_name: '计算机2401' } as any },
        { id: 5, assignment_id: assignmentId.value, student_id: 5, content: '已签到', score: null, feedback: null, status: 'pending', student: { id: 5, name: '王五', student_no: '2024005', class_name: '计算机2402' } as any },
        { assignment_id: assignmentId.value, student_id: 6, content: null, score: null, feedback: null, status: 'absent', student: { id: 6, name: '赵六', student_no: '2024006', class_name: '计算机2402' } as any },
        { assignment_id: assignmentId.value, student_id: 7, content: null, score: null, feedback: null, status: 'absent', student: { id: 7, name: '孙七', student_no: '2024007', class_name: '计算机2402' } as any },
        { assignment_id: assignmentId.value, student_id: 8, content: null, score: null, feedback: null, status: 'absent', student: { id: 8, name: '周八', student_no: '2024008', class_name: '计算机2402' } as any },
      ] as Submission[],
    }
  }
}

async function updateSubmission(sub: Submission) {
  if (!sub.id) {
    try {
      if (sub.status === 'absent') {
        await api.submissions.markAbsent({
          assignment_id: sub.assignment_id,
          student_id: sub.student_id,
          feedback: sub.feedback || undefined,
        })
      }
      return
    } catch (e) { return }
  }
  try {
    await api.submissions.update(sub.id, {
      score: sub.score,
      feedback: sub.feedback,
      status: sub.score !== null && sub.score !== undefined ? 'graded' : sub.status,
    })
  } catch (e) { /* ignore for demo */ }
}

async function gradePresent(sub: Submission) {
  sub.score = assignment.value?.max_score ?? 100
  sub.feedback = '出勤正常'
  sub.status = 'graded'
  await updateSubmission(sub)
  await loadAssignment()
}

async function gradeAbsent(sub: Submission) {
  sub.score = 0
  sub.feedback = '缺勤'
  sub.status = 'absent'
  await updateSubmission(sub)
  await loadAssignment()
}

async function markAllPresent() {
  if (!assignment.value) return
  if (!confirm('确定将所有学生标记为全勤（满分）吗？')) return
  try {
    const grades = assignment.value.submissions.map(s => ({
      student_id: s.student_id,
      score: assignment.value!.max_score,
      feedback: '出勤正常',
      status: 'graded' as const,
    }))
    await api.submissions.batchGrade({ assignment_id: assignmentId.value, grades })
  } catch (e) {
    assignment.value.submissions.forEach(s => {
      s.score = assignment.value!.max_score
      s.feedback = '出勤正常'
      s.status = 'graded'
    })
  }
  await loadAssignment()
}

async function markAllAbsent() {
  if (!assignment.value) return
  if (!confirm('确定将所有学生标记为缺勤吗？')) return
  try {
    const grades = assignment.value.submissions.map(s => ({
      student_id: s.student_id,
      score: 0,
      feedback: '缺勤',
      status: 'absent' as const,
    }))
    await api.submissions.batchGrade({ assignment_id: assignmentId.value, grades })
  } catch (e) {
    assignment.value.submissions.forEach(s => {
      s.score = 0
      s.feedback = '缺勤'
      s.status = 'absent'
    })
  }
  await loadAssignment()
}

onMounted(loadAssignment)
</script>
