const loginTab = document.getElementById("loginTab");
const registerTab = document.getElementById("registerTab");
const loginForm = document.getElementById("loginForm");
const registerForm = document.getElementById("registerForm");

loginTab.addEventListener("click", () => {
  loginForm.classList.remove("hidden");
  registerForm.classList.add("hidden");
  loginTab.classList.add("bg-white","text-blue-600");
  registerTab.classList.remove("bg-white","text-blue-600");
  registerTab.classList.add("text-gray-500");
});

registerTab.addEventListener("click", () => {
  registerForm.classList.remove("hidden");
  loginForm.classList.add("hidden");
  registerTab.classList.add("bg-white","text-blue-600");
  registerTab.classList.remove("text-gray-500");
  loginTab.classList.remove("bg-white","text-blue-600");
  loginTab.classList.add("text-gray-500");
});

// تحقق حقول تسجيل الدخول قبل الإرسال
const loginEmail = document.getElementById("emailLogin");
const loginPass  = document.getElementById("password"); // <<< تطابق مع HTML

if (loginEmail && loginPass && loginForm) {
  const form = loginForm.querySelector("form");
  form.addEventListener("submit", (e) => {
    const emailErr = document.getElementById("emailError");
    const passErr  = document.getElementById("passwordError");
    if (emailErr) emailErr.textContent = "";
    if (passErr)  passErr.textContent = "";

    let hasError = false;
    if (!loginEmail.value.trim()) {
      if (emailErr) emailErr.textContent = "Email is required";
      hasError = true;
    }
    if (!loginPass.value.trim()) {
      if (passErr) passErr.textContent = "Password is required";
      hasError = true;
    }
    if (hasError) e.preventDefault();
  });
}