function showToast(msg) {
    // Basic toast, using alert for testing simplicity if no toast div exists
    alert(msg);
}

function adminLogin() {
    const email = document.getElementById('adminEmail').value;
    const password = document.getElementById('adminPassword').value;
    
    const formData = new FormData();
    formData.append('action', 'login');
    formData.append('email', email);
    formData.append('password', password);
    
    fetch('api/auth.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.role === 'admin') {
                document.getElementById('adminLoginBox').style.display = 'none';
                document.getElementById('adminPanel').style.display = 'block';
                loadAdminData();
            } else {
                document.getElementById('adminError').innerText = "Access Denied: Invalid admin credentials";
            }
        });
}

function adminLogout() {
    const formData = new FormData();
    formData.append('action', 'logout');
    fetch('api/auth.php', { method: 'POST', body: formData })
        .then(() => {
            window.location.reload();
        });
}

function checkAdminSession() {
    fetch('api/auth.php?action=check_session')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success' && data.logged_in && data.user.role === 'admin') {
                document.getElementById('adminLoginBox').style.display = 'none';
                document.getElementById('adminPanel').style.display = 'block';
                loadAdminData();
            }
        });
}

function loadAdminData() {
    // Stats
    fetch('api/admin_api.php?action=stats')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('statProblems').innerText = data.data.problems;
                document.getElementById('statUsers').innerText = data.data.users;
                document.getElementById('statAds').innerText = data.data.ad_reqs;
                document.getElementById('statSubs').innerText = data.data.subs;
            }
        });

    // Users
    fetch('api/admin_api.php?action=users')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('usersTable').querySelector('tbody');
            tbody.innerHTML = data.data.map(u => `
                <tr>
                    <td>${u.id}</td>
                    <td>${u.name}</td>
                    <td>${u.email}</td>
                    <td><span class="btn-small" style="background:${u.role==='admin'?'#3498db':'#2ecc71'}">${u.role}</span></td>
                    <td><button class="btn-small" onclick="deleteUser(${u.id})">Del</button></td>
                </tr>
            `).join('');
        });

    // Problems
    fetch('api/admin_api.php?action=problems')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('problemsTable').querySelector('tbody');
            tbody.innerHTML = data.data.map(p => `
                <tr>
                    <td>${p.id}</td>
                    <td>${p.title}</td>
                    <td>${p.user_name}</td>
                    <td>${p.category}</td>
                    <td><button class="btn-small" onclick="deleteProblem(${p.id})">Del</button></td>
                </tr>
            `).join('');
        });

    // Subs config
    fetch('api/admin_api.php?action=subscribers').then(r=>r.json()).then(data => {
        document.getElementById('subsList').innerHTML = data.data.map(s => `<li><i class="fas fa-envelope"></i> ${s.email} <small style="color:#aaa;">${s.created_at}</small></li>`).join('');
    });
    
    // Ads config
    fetch('api/admin_api.php?action=ad_requests').then(r=>r.json()).then(data => {
        document.getElementById('adsList').innerHTML = data.data.map(a => `<li style="margin-bottom:10px; border-bottom:1px solid #333;"><i class="fas fa-building"></i> <strong>${a.company}</strong> (${a.package})<br><small>${a.message}</small></li>`).join('');
    });
}

function deleteUser(id) {
    if(!confirm("Are you sure you want to delete this user?")) return;
    const fd = new FormData(); fd.append('action', 'delete_user'); fd.append('id', id);
    fetch('api/admin_api.php', { method:'POST', body: fd }).then(r=>r.json()).then(d => { showToast(d.message); loadAdminData(); });
}

function deleteProblem(id) {
    if(!confirm("Are you sure you want to delete this problem?")) return;
    const fd = new FormData(); fd.append('action', 'delete_problem'); fd.append('id', id);
    fetch('api/admin_api.php', { method:'POST', body: fd }).then(r=>r.json()).then(d => { showToast(d.message); loadAdminData(); });
}

document.addEventListener("DOMContentLoaded", checkAdminSession);
