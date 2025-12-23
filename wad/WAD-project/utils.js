function checkSecurity() {
    const role = localStorage.getItem("userRole"); 
    if (role !== "Administrator") {
        alert("Access Denied!");
        window.location.href = "index.html"; 
    }
}