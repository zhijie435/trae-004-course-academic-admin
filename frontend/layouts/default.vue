<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 py-3">
        <div class="flex items-center justify-between">
          <NuxtLink to="/" class="text-xl font-bold text-gray-900">
            在线课程教务系统
            <span class="ml-2 text-sm font-normal text-muted">
              {{ appState.isTeacher() ? '（教师端）' : '（学员端）' }}
            </span>
          </NuxtLink>
          <nav class="flex items-center gap-6">
            <NuxtLink to="/" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">首页</NuxtLink>

            <template v-if="appState.isTeacher()">
              <NuxtLink to="/courses" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">课程管理</NuxtLink>
              <NuxtLink to="/students" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">学生管理</NuxtLink>
            </template>

            <template v-else>
              <NuxtLink to="/student/courses" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">报名课程</NuxtLink>
              <NuxtLink to="/student/my-courses" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">我的课程</NuxtLink>
              <NuxtLink to="/student/sign-in" class="text-sm text-gray-600 hover:text-gray-900 transition-colors">课堂签到</NuxtLink>
            </template>

            <div class="flex items-center gap-2 pl-4 border-l border-gray-200">
              <template v-if="appState.isStudent() && appState.state.currentStudent">
                <span class="text-sm text-gray-600">
                  {{ appState.state.currentStudent.name }}
                  <span class="text-muted">({{ appState.state.currentStudent.student_no }})</span>
                </span>
                <select
                  class="form-input text-sm py-1"
                  style="width: auto;"
                  :value="appState.state.currentStudent.id"
                  @change="onStudentChange($event)"
                >
                  <option v-for="s in appState.DEMO_STUDENTS" :key="s.id" :value="s.id">
                    {{ s.name }}
                  </option>
                </select>
              </template>
              <button
                class="btn btn-sm"
                :class="appState.isTeacher() ? 'btn-outline' : 'btn-primary'"
                @click="appState.setRole('teacher')"
              >
                教师
              </button>
              <button
                class="btn btn-sm"
                :class="appState.isStudent() ? 'btn-primary' : 'btn-outline'"
                @click="appState.setRole('student')"
              >
                学员
              </button>
            </div>
          </nav>
        </div>
      </div>
    </header>
    <main class="max-w-7xl mx-auto px-4 py-6">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
const appState = useAppState()

function onStudentChange(e: Event) {
  const target = e.target as HTMLSelectElement
  const id = Number(target.value)
  const student = appState.DEMO_STUDENTS.find(s => s.id === id)
  if (student) {
    appState.setCurrentStudent(student)
    navigateTo('/student/my-courses')
  }
}
</script>
