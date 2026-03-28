// Sample data
const problems = [
    { category: 'TECH', intent: 'CO-FOUNDER', title: 'AI DIAGNOSTIC TOOL FOR RURAL CLINICS', desc: 'Looking for technical co-founder to build AI-powered diagnostic tool for rural clinics...', views: 45, user: 'PS', time: '2m', locked: true },
    { category: 'HEALTH', intent: 'DEV TEAM', title: 'TELEMEDICINE PLATFORM FOR ELDERLY', desc: 'Need full-stack team to build voice-first telemedicine app...', views: 128, user: 'RK', time: '1h', locked: false },
    { category: 'FINTECH', intent: 'CONSULTANT', title: 'MICRO-INVESTMENT APP FOR RURAL WOMEN', desc: 'Seeking fintech consultant for micro-investment platform...', views: 67, user: 'MJ', time: '3h', locked: true },
    { category: 'EDU', intent: 'TEAM', title: 'OFFLINE-FIRST LEARNING FOR RURAL STUDENTS', desc: 'Looking for team to build offline-first learning platform...', views: 82, user: 'SP', time: '5h', locked: false },
    { category: 'AGRI', intent: 'CO-FOUNDER', title: 'DIRECT FARMER-TO-BUYER MARKETPLACE', desc: 'Seeking technical co-founder for farmer marketplace...', views: 56, user: 'AK', time: '1d', locked: true },
    { category: 'FINTECH', intent: 'CONSULTANT', title: 'MICRO-CREDIT FOR WOMEN ENTREPRENEURS', desc: 'Need fintech consultant for micro-credit platform...', views: 73, user: 'RJ', time: '1d', locked: false },
    { category: 'TECH', intent: 'CO-FOUNDER', title: 'AI CHATBOT FOR LEGAL AID', desc: 'Looking for AI expert to build legal aid chatbot...', views: 34, user: 'SN', time: '2d', locked: true },
    { category: 'HEALTH', intent: 'DEV TEAM', title: 'MENTAL HEALTH APP FOR STUDENTS', desc: 'Need team to build anonymous mental health app...', views: 92, user: 'PK', time: '2d', locked: false },
    { category: 'EDU', intent: 'TEAM', title: 'VOCATIONAL TRAINING PLATFORM', desc: 'Looking for team to build skill development platform...', views: 41, user: 'AM', time: '3d', locked: true },
    { category: 'AGRI', intent: 'CO-FOUNDER', title: 'CROP DISEASE DETECTION APP', desc: 'Seeking technical co-founder for AI crop disease detection...', views: 63, user: 'VP', time: '3d', locked: false },
    { category: 'FINTECH', intent: 'CONSULTANT', title: 'DIGITAL PAYMENTS FOR RURAL INDIA', desc: 'Need consultant for UPI-based payment solution...', views: 28, user: 'SK', time: '4d', locked: true },
    { category: 'TECH', intent: 'CO-FOUNDER', title: 'IOT DEVICES FOR SMART FARMING', desc: 'Looking for hardware expert to build IoT sensors...', views: 51, user: 'RT', time: '4d', locked: false },
    { category: 'HEALTH', intent: 'DEV TEAM', title: 'WEARABLE DEVICE FOR ELDERLY CARE', desc: 'Need team to build wearable with fall detection...', views: 37, user: 'DM', time: '5d', locked: true },
    { category: 'EDU', intent: 'TEAM', title: 'GAMIFIED LEARNING FOR KIDS', desc: 'Looking for team to build educational games...', views: 44, user: 'AG', time: '5d', locked: false },
    { category: 'FINTECH', intent: 'CONSULTANT', title: 'BLOCKCHAIN FOR LAND RECORDS', desc: 'Need blockchain expert for land record system...', views: 19, user: 'KS', time: '6d', locked: true },
    { category: 'AGRI', intent: 'CO-FOUNDER', title: 'COLD STORAGE MONITORING SYSTEM', desc: 'Need IoT expert for cold storage monitoring...', views: 32, user: 'BM', time: '6d', locked: false }
];

const stories = [
    { title: 'FROM RURAL CLINIC TO 10M USERS', desc: 'How a healthcare problem posted on PROBLEMidea became a platform serving millions across India.' },
    { title: 'THE SOLO DEVELOPER WHO FOUND HIS CO-FOUNDER', desc: 'A developer\'s journey from browsing problems to building a funded startup with his co-founder.' },
    { title: 'FINTECH REVOLUTION IN RURAL INDIA', desc: 'How a micro-investment idea became a platform for 500,000+ rural women.' },
    { title: 'EDTECH FOR THE UNREACHED', desc: 'An offline-first learning platform that reached 1M+ rural students.' }
];

const pricingPlans = [
    { name: 'BASIC', price: '2,999', features: ['15 DAYS IN FEED', '1 CATEGORY', 'BASIC REPORT'], popular: false },
    { name: 'PRO', price: '4,999', features: ['30 DAYS IN FEED', '3 CATEGORIES', 'PERFORMANCE REPORT', 'SPOTLIGHT'], popular: true },
    { name: 'ENTERPRISE', price: '9,999', features: ['60 DAYS IN FEED', 'ALL CATEGORIES', 'ADVANCED ANALYTICS', 'DEDICATED SUPPORT'], popular: false }
];

// Page content for footer links
const pageContents = {
    'newsletter': { title: 'NEWSLETTER', content: 'Subscribe to our weekly newsletter featuring the latest problems, developer stories, and tech insights. Get curated content delivered straight to your inbox.<br><br><input type="email" placeholder="Enter your email address" style="width:100%; padding:12px 15px; margin-bottom:15px; border: 1px solid var(--border-color); border-radius: 8px; background: rgba(255, 255, 255, 0.05); color: var(--text-primary); outline: none;"><button class="btn-primary" style="width:100%; padding:14px;" onclick="showToast(\'SUBSCRIBED SUCCESSFULLY!\'); closeModal(\'pageModal\');">SUBSCRIBE</button>' },
    'apps': { title: 'APPS', content: 'Download our mobile apps for iOS and Android. Access IdeaventureX on the go, get real-time notifications, and never miss a match.' },
    'about': { title: 'ABOUT US', content: 'IdeaventureX is the first gated marketplace connecting problem owners with verified developers. Founded in 2026, we help turn real-world problems into successful ventures.' },
    'faq': { title: 'FREQUENTLY ASKED QUESTIONS', content: 'Find answers to common questions about posting problems, finding developers, verification process, and how our matching system works.' },
    'terms': { title: 'TERMS OF SERVICE & NDA', content: '<strong>1. Non-Disclosure & Idea Protection:</strong> By using IdeaventureX, both Problem Owners and Developers agree to mutual Non-Disclosure obligations. All proprietary concepts shared within matched groups remain strictly confidential and the intellectual property of the Problem Owner unless formally transferred.<br><br><strong>2. User Conduct:</strong> Users must provide accurate skill sets. Any attempt to scrape, copy, or bypass the platform to steal ideas will result in a permanent ban and potential legal action under the governing Intellectual Property laws.<br><br><strong>3. Liability:</strong> IdeaventureX acts solely as a matching mediator. We ensure verified connections but are not liable for external contracts signed between matched parties.' },
    'privacy': { title: 'PRIVACY POLICY', content: '<strong>1. Data Collection:</strong> We collect essential data (skills, email, usage analytics) to provide accurate matchmaking. We do not sell your personal data to third parties. We use industry-standard encryption for all data at rest and in transit.<br><br><strong>2. Intellectual Property Privacy:</strong> Information posted in the "Live Problems" section remains obfuscated to unverified users or standard visitors. Only strictly necessary surface data is public for SEO purposes.<br><br><strong>3. Your Rights:</strong> You have the right to request full deletion of your account and associated data. However, records of accepted NDA contracts may be retained for legal compliance.' },
    'privacy-choices': { title: 'PRIVACY CHOICES', content: 'Manage your privacy preferences. Control what data you share, opt out of data collection, and customize your experience on IdeaventureX.' },
    'ilms': { title: 'ILMS.TXT', content: 'Information Location and Management Standards. This page contains machine-readable information about our platform, data handling practices, and compliance standards for AI indexing.' },
    'advertise': { title: 'ADVERTISE WITH US', content: 'Reach 1.8K+ elite developers and problem solvers. Promote your tools, APIs, and services to the most active innovators in our network.' },
    'contact': { title: 'CONTACT US', content: 'Have questions or feedback? Reach out to us at hello@ideaventurex.com. Our team typically responds within 24 hours.' },
    'twitter': { title: 'TWITTER', content: 'Follow us on Twitter @IdeaventureX for the latest updates, featured problems, and community highlights. Join the conversation!' },
    'linkedin': { title: 'LINKEDIN', content: 'Connect with us on LinkedIn. Follow our company page for professional updates, success stories, and networking opportunities.' }
};

let userType = 'owner';
let isLoggedIn = false;

// Mobile menu toggle
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
    }
}

// Dropdown handlers
document.addEventListener('DOMContentLoaded', function() {
    const profileBadge = document.getElementById('profileBadge');
    if (profileBadge) {
        profileBadge.addEventListener('click', function(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('logoutDropdown');
            dropdown.classList.toggle('show');
        });
    }

    const adminProfileBadge = document.getElementById('adminProfileBadge');
    if (adminProfileBadge) {
        adminProfileBadge.addEventListener('click', function(event) {
            event.stopPropagation();
            const dropdown = document.getElementById('adminLogoutDropdown');
            dropdown.classList.toggle('show');
        });
    }

    document.addEventListener('click', function() {
        const dropdown = document.getElementById('logoutDropdown');
        if (dropdown) dropdown.classList.remove('show');
        
        const adminDropdown = document.getElementById('adminLogoutDropdown');
        if (adminDropdown) adminDropdown.classList.remove('show');
    });

    const dropdown = document.getElementById('logoutDropdown');
    if (dropdown) {
        dropdown.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }

    const adminDropdown = document.getElementById('adminLogoutDropdown');
    if (adminDropdown) {
        adminDropdown.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }

    // Type selector functionality
    const typeOptions = document.querySelectorAll('.type-option');
    typeOptions.forEach(option => {
        option.addEventListener('click', function() {
            typeOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            
            const uspList = document.getElementById('uspList');
            if (this.textContent.includes('PROBLEM OWNER')) {
                uspList.innerHTML = `
                    <li><i class="fas fa-lightbulb"></i> POST REAL-WORLD PROBLEMS YOU FACE</li>
                    <li><i class="fas fa-users"></i> CONNECT WITH VERIFIED DEVELOPERS</li>
                    <li><i class="fas fa-shield-alt"></i> IP PROTECTION & NDA READY</li>
                    <li><i class="fas fa-chart-line"></i> TURN IDEAS INTO SCALABLE VENTURES</li>
                `;
            } else {
                uspList.innerHTML = `
                    <li><i class="fas fa-code"></i> WORK ON REAL PROBLEMS WITH IMPACT</li>
                    <li><i class="fas fa-briefcase"></i> FIND CO-FOUNDERS & PAYING PROJECTS</li>
                    <li><i class="fas fa-award"></i> BUILD PORTFOLIO & GET RECOGNITION</li>
                    <li><i class="fas fa-handshake"></i> JOIN A COMMUNITY OF TOP DEVELOPERS</li>
                `;
            }
        });
    });

    loadProblems();
    loadStories();
    loadPricing();
    loadDashboardData();
    updateDate();
});

// Subscribe alert function
function subscribeAlert() {
    alert('Please login or sign up to subscribe');
}

// Filter functions for home page
function filterProblems(category, event) {
    document.querySelectorAll('#home-page .category-link').forEach(c => c.classList.remove('active'));
    event.target.classList.add('active');
    showToast(`FILTERING BY ${category.toUpperCase()}`);
    
    const homeGrid = document.getElementById('homeProblemsGrid');
    if (category === 'all') {
        homeGrid.innerHTML = problems.map(p => createProblemCard(p)).join('');
    } else {
        const filtered = problems.filter(p => p.category.toLowerCase() === category.toLowerCase());
        homeGrid.innerHTML = filtered.map(p => createProblemCard(p)).join('');
    }
}

// Filter functions for explore page
function filterExploreProblems(category, event) {
    document.querySelectorAll('#explore-page .category-link').forEach(c => c.classList.remove('active'));
    event.target.classList.add('active');
    showToast(`EXPLORE FILTER: ${category.toUpperCase()}`);
    
    const exploreGrid = document.getElementById('exploreProblemsGrid');
    if (category === 'all') {
        exploreGrid.innerHTML = problems.map(p => createProblemCard(p)).join('');
    } else {
        const filtered = problems.filter(p => p.category.toLowerCase() === category.toLowerCase());
        exploreGrid.innerHTML = filtered.map(p => createProblemCard(p)).join('');
    }
}

function showPage(pageKey) {
    const page = pageContents[pageKey];
    if (page) {
        document.getElementById('pageModalTitle').textContent = page.title;
        document.getElementById('pageModalContent').innerHTML = `<p>${page.content}</p>`;
        openModal('page');
    }
}

function closeDropdown() {
    const dropdown = document.getElementById('logoutDropdown');
    if (dropdown) dropdown.classList.remove('show');
}

function closeAdminDropdown() {
    const dropdown = document.getElementById('adminLogoutDropdown');
    if (dropdown) dropdown.classList.remove('show');
}

function loadProblems() {
    const homeGrid = document.getElementById('homeProblemsGrid');
    const exploreGrid = document.getElementById('exploreProblemsGrid');
    
    if (homeGrid) {
        homeGrid.innerHTML = problems.map(p => createProblemCard(p)).join('');
    }
    if (exploreGrid) {
        exploreGrid.innerHTML = problems.slice(0, 12).map(p => createProblemCard(p)).join('');
    }
}

function createProblemCard(p) {
    if (p.locked) {
        return `<div class="problem-card"><div class="card-badges"><span class="badge industry">${p.category}</span><span class="badge intent">${p.intent}</span><span class="badge status">LIVE</span></div><div class="problem-title">${p.title}</div><div class="blur-container"><div class="blur-content problem-desc">${p.desc}</div><div class="unlock-overlay" onclick="showToast('VERIFY OTP TO UNLOCK')">VERIFY</div></div><div class="card-footer"><span class="view-count"><i class="far fa-eye"></i> ${p.views}</span><div><span class="user-avatar-sm">${p.user}</span> <span class="timestamp">${p.time}</span></div></div></div>`;
    } else {
        return `<div class="problem-card"><div class="card-badges"><span class="badge industry">${p.category}</span><span class="badge intent">${p.intent}</span><span class="badge status">LIVE</span></div><div class="problem-title">${p.title}</div><div class="problem-desc">${p.desc}</div><div class="card-footer"><button class="interest-btn" onclick="showToast('INTEREST SENT')">REQUEST</button><span class="view-count"><i class="far fa-eye"></i> ${p.views} <span class="timestamp">${p.time}</span></span></div></div>`;
    }
}

function loadStories() {
    const storiesGrid = document.getElementById('storiesGrid');
    if (storiesGrid) {
        storiesGrid.innerHTML = stories.map(s => `<div class="problem-card"><h3 style="font-size:20px; margin-bottom:15px;">${s.title}</h3><p style="color:var(--text-secondary); margin-bottom:20px;">${s.desc}</p><button class="interest-btn" onclick="showToast('READING STORY')">READ STORY</button></div>`).join('');
    }
}

function loadPricing() {
    const pricingContainer = document.getElementById('pricingCards');
    if (pricingContainer) {
        pricingContainer.innerHTML = pricingPlans.map(plan => `
            <div class="pricing-card ${plan.popular ? 'popular' : ''}">
                ${plan.popular ? '<div class="popular-badge">MOST POPULAR</div>' : ''}
                <h3>${plan.name}</h3>
                <div class="pricing-price">₹${plan.price}</div>
                <ul class="pricing-features">${plan.features.map(f => `<li><i class="fas fa-check-circle"></i> ${f}</li>`).join('')}</ul>
                <button class="${plan.popular ? 'btn-primary' : 'btn-outline'}" style="width:100%;" onclick="showToast('SELECTED ${plan.name}')">SELECT</button>
            </div>`).join('');
    }
}

function loadDashboardData() {
    const statsContainer = document.getElementById('dashboardStats');
    if (statsContainer) {
        statsContainer.innerHTML = `
            <div class="stat-card-large"><div class="stat-icon">👁️</div><h3>156</h3><p>PROFILE VIEWS</p><div class="stat-trend">↑ 12%</div></div>
            <div class="stat-card-large"><div class="stat-icon">📋</div><h3>12</h3><p>PROBLEMS</p><div class="stat-trend">+3 this week</div></div>
            <div class="stat-card-large"><div class="stat-icon">❤️</div><h3>8</h3><p>INTERESTS</p><div class="stat-trend">2 new</div></div>
            <div class="stat-card-large"><div class="stat-icon">🤝</div><h3>3</h3><p>MATCHES</p><div class="stat-trend">1 in progress</div></div>`;
    }

    const chartContainer = document.getElementById('activityChart');
    if (chartContainer) {
        const heights = [40, 65, 80, 45, 90, 120, 150, 110, 70, 85, 95, 60];
        chartContainer.innerHTML = heights.map((h, i) => `<div class="chart-column"><div class="chart-bar" style="height: ${h}px;"></div><span class="chart-label">${['J','F','M','A','M','J','J','A','S','O','N','D'][i]}</span></div>`).join('');
    }

    const matchesContainer = document.getElementById('recentMatches');
    if (matchesContainer) {
        matchesContainer.innerHTML = `
            <div class="match-card"><div class="match-avatar">PS</div><div class="match-info"><h4>Priya S.</h4><div class="role">AI/ML Expert</div><div class="match-skills"><span class="skill-tag">Python</span><span class="skill-tag">TensorFlow</span></div><div class="match-actions"><button class="match-btn accept" onclick="showToast('ACCEPTED')">Accept</button><button class="match-btn message" onclick="showToast('MESSAGE')">Message</button></div></div></div>
            <div class="match-card"><div class="match-avatar">RK</div><div class="match-info"><h4>Rahul K.</h4><div class="role">Full Stack Dev</div><div class="match-skills"><span class="skill-tag">React</span><span class="skill-tag">Node.js</span></div><div class="match-actions"><button class="match-btn accept" onclick="showToast('ACCEPTED')">Accept</button><button class="match-btn message" onclick="showToast('MESSAGE')">Message</button></div></div></div>`;
    }

    const problemsList = document.getElementById('myProblemsList');
    if (problemsList) {
        problemsList.innerHTML = `
            <div class="problem-item"><div class="problem-info"><h4>AI Diagnostic Tool</h4><div class="meta"><span>👁️ 45 views</span><span>📅 Posted 2 days ago</span></div></div><span class="problem-status status-live">LIVE</span></div>
            <div class="problem-item"><div class="problem-info"><h4>Telemedicine Platform</h4><div class="meta"><span>👁️ 128 views</span><span>📅 Posted 1 week ago</span></div></div><span class="problem-status status-matched">MATCHED</span></div>
            <div class="problem-item"><div class="problem-info"><h4>Micro-Investment App</h4><div class="meta"><span>👁️ 67 views</span><span>📅 Posted 2 weeks ago</span></div></div><span class="problem-status status-draft">DRAFT</span></div>`;
    }

    const interestsGrid = document.getElementById('interestsGrid');
    if (interestsGrid) {
        interestsGrid.innerHTML = `
            <div class="interest-card"><h4>AI Diagnostic Tool</h4><div class="company">by Priya S.</div><div class="interest-progress"><div class="progress-fill" style="width:60%"></div></div><div class="interest-footer"><span>60% match</span><span>2 days ago</span></div></div>
            <div class="interest-card"><h4>Telemedicine Platform</h4><div class="company">by Rahul K.</div><div class="interest-progress"><div class="progress-fill" style="width:85%"></div></div><div class="interest-footer"><span>85% match</span><span>5 days ago</span></div></div>
            <div class="interest-card"><h4>Micro-Credit App</h4><div class="company">by Anand M.</div><div class="interest-progress"><div class="progress-fill" style="width:40%"></div></div><div class="interest-footer"><span>40% match</span><span>1 week ago</span></div></div>`;
    }

    const matchesGrid = document.getElementById('matchesGrid');
    if (matchesGrid) {
        matchesGrid.innerHTML = `
            <div class="match-card"><div class="match-avatar">PS</div><div class="match-info"><h4>Priya S.</h4><div class="role">AI/ML Expert</div><div class="match-skills"><span class="skill-tag">Python</span><span class="skill-tag">TensorFlow</span></div><div class="match-actions"><button class="match-btn message" onclick="showToast('MESSAGE')">Message</button></div></div></div>
            <div class="match-card"><div class="match-avatar">RK</div><div class="match-info"><h4>Rahul K.</h4><div class="role">Full Stack Dev</div><div class="match-skills"><span class="skill-tag">React</span><span class="skill-tag">Node.js</span></div><div class="match-actions"><button class="match-btn message" onclick="showToast('MESSAGE')">Message</button></div></div></div>
            <div class="match-card"><div class="match-avatar">AM</div><div class="match-info"><h4>Anand M.</h4><div class="role">Blockchain Dev</div><div class="match-skills"><span class="skill-tag">Solidity</span><span class="skill-tag">Web3</span></div><div class="match-actions"><button class="match-btn message" onclick="showToast('MESSAGE')">Message</button></div></div></div>
            <div class="match-card"><div class="match-avatar">SP</div><div class="match-info"><h4>Sunita P.</h4><div class="role">UX Designer</div><div class="match-skills"><span class="skill-tag">Figma</span><span class="skill-tag">UI/UX</span></div><div class="match-actions"><button class="match-btn message" onclick="showToast('MESSAGE')">Message</button></div></div></div>`;
    }
}

function submitAdRequest() {
    const name = document.getElementById('adName')?.value;
    const company = document.getElementById('adCompany')?.value;
    const email = document.getElementById('adEmail')?.value;
    
    if (!name || !company || !email) {
        showToast('Please fill all required fields');
        return;
    }
    
    showToast('REQUEST SUBMITTED! OUR TEAM WILL CONTACT YOU SOON.');
    
    if (document.getElementById('adName')) document.getElementById('adName').value = '';
    if (document.getElementById('adCompany')) document.getElementById('adCompany').value = '';
    if (document.getElementById('adEmail')) document.getElementById('adEmail').value = '';
    if (document.getElementById('adPhone')) document.getElementById('adPhone').value = '';
    if (document.getElementById('adMessage')) document.getElementById('adMessage').value = '';
    if (document.getElementById('adTerms')) document.getElementById('adTerms').checked = false;
}

function updateDate() {
    const dateElement = document.getElementById('currentDate');
    if (dateElement) {
        const now = new Date();
        dateElement.textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
}

window.onscroll = function() {
    let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    let scrolled = (winScroll / height) * 100;
    document.getElementById('progressBar').style.width = scrolled + '%';
    if (winScroll > 300) document.getElementById('backToTop').classList.add('show');
    else document.getElementById('backToTop').classList.remove('show');
};

function scrollToTop() { window.scrollTo({ top: 0, behavior: 'smooth' }); }

function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    const icon = document.querySelector('.theme-toggle i');
    icon.className = document.body.classList.contains('dark-mode') ? 'fas fa-sun' : 'fas fa-moon';
}

function logout() {
    document.getElementById('authButtons').style.display = 'flex';
    document.getElementById('profileBadge').style.display = 'none';
    document.getElementById('logoutDropdown')?.classList.remove('show');
    document.getElementById('adminLogoutDropdown')?.classList.remove('show');
    document.getElementById('adminLoginBox').style.display = 'block';
    document.getElementById('adminPanel').style.display = 'none';
    showToast('LOGGED OUT SUCCESSFULLY');
    switchPage('home');
    document.getElementById('mobileMenu').style.display = 'none';
}

function switchPage(page) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active-page'));
    document.getElementById(page + '-page').classList.add('active-page');
    
    document.querySelectorAll('.nav-links a').forEach(a => a.classList.remove('active'));
    const links = document.querySelectorAll('.nav-links a');
    for(let link of links) {
        if(link.textContent.trim().toLowerCase() === page.toLowerCase()) {
            link.classList.add('active');
            break;
        }
    }
    closeDropdown();
    document.getElementById('mobileMenu').style.display = 'none';
}

function switchDashboardTab(tab) {
    document.querySelectorAll('.dashboard-tab').forEach(t => t.classList.remove('active'));
    event.target.classList.add('active');
    
    document.getElementById('overview-tab').style.display = 'none';
    document.getElementById('problems-tab').style.display = 'none';
    document.getElementById('interests-tab').style.display = 'none';
    document.getElementById('matches-tab').style.display = 'none';
    document.getElementById(tab + '-tab').style.display = 'block';
}

function selectType(type) {
    document.querySelectorAll('.type-option').forEach(o => o.classList.remove('active'));
    event.target.classList.add('active');
    userType = type;
    
    const uspList = document.getElementById('uspList');
    if (type === 'owner') {
        uspList.innerHTML = `
            <li><i class="fas fa-lightbulb"></i> POST REAL-WORLD PROBLEMS YOU FACE</li>
            <li><i class="fas fa-users"></i> CONNECT WITH VERIFIED DEVELOPERS</li>
            <li><i class="fas fa-shield-alt"></i> IP PROTECTION & NDA READY</li>
            <li><i class="fas fa-chart-line"></i> TURN IDEAS INTO SCALABLE VENTURES</li>
        `;
    } else {
        uspList.innerHTML = `
            <li><i class="fas fa-code"></i> WORK ON REAL PROBLEMS WITH IMPACT</li>
            <li><i class="fas fa-briefcase"></i> FIND CO-FOUNDERS & PAYING PROJECTS</li>
            <li><i class="fas fa-award"></i> BUILD PORTFOLIO & GET RECOGNITION</li>
            <li><i class="fas fa-handshake"></i> JOIN A COMMUNITY OF TOP DEVELOPERS</li>
        `;
    }
}

function openModal(t) { 
    document.getElementById(t + 'Modal').style.display = 'flex'; 
}

function closeModal(id) { 
    document.getElementById(id).style.display = 'none'; 
}

function openEditProfileModal() {
    closeDropdown();
    openModal('editProfile');
}

function demoLogin() {
    closeModal('loginModal');
    document.getElementById('authButtons').style.display = 'none';
    document.getElementById('profileBadge').style.display = 'flex';
    isLoggedIn = true;
    showToast('WELCOME BACK!');
    window.location.href = 'dashboard.html';
}

function demoSignup() {
    const role = document.getElementById('signupRole').value;
    userType = role.includes('OWNER') ? 'owner' : 'developer';
    
    closeModal('signupModal');
    document.getElementById('authButtons').style.display = 'none';
    document.getElementById('profileBadge').style.display = 'flex';
    document.getElementById('profileType').textContent = role.includes('OWNER') ? 'OWNER' : 'DEVELOPER';
    isLoggedIn = true;
    showToast('ACCOUNT CREATED!');
    window.location.href = 'dashboard.html';
}

function adminLogin() {
    document.getElementById('adminLoginBox').style.display = 'none';
    document.getElementById('adminPanel').style.display = 'block';
    showToast('WELCOME ADMIN');
}

function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 2000);
}

window.onclick = function(e) { 
    if(e.target.classList.contains('modal')) e.target.style.display = 'none'; 
    
    if (!e.target.closest('.hamburger-menu') && !e.target.closest('#mobileMenu')) {
        document.getElementById('mobileMenu').style.display = 'none';
    }
};