/**
 * e-GURO LMS Store & Session Manager
 * Provides reliable data persistence across Admin, Teacher, and Student portals.
 */

const LMSStore = (() => {
    const DEFAULT_USERS = [
        { id: 1, userCode: 'ADMIN-01', name: 'System Administrator', email: 'admin@eguro.edu.ph', role: 'admin', password: 'password123', createdAt: '2026-10-01' },
        { id: 2, userCode: 'DEAN-01', name: 'Dean Maria Santos', email: 'dean@eguro.edu.ph', role: 'dean', password: 'password123', createdAt: '2026-10-02' },
        { id: 3, userCode: 'TECH-01', name: 'Prof. Juan Dela Cruz', email: 'teacher@eguro.edu.ph', role: 'teacher', password: 'password123', createdAt: '2026-10-05' },
        { id: 4, userCode: 'TECH-02', name: 'Prof. Sarah Connor', email: 'sconnor@eguro.edu.ph', role: 'teacher', password: 'password123', createdAt: '2026-10-08' },
        { id: 5, userCode: '2026-001', name: 'Bruno Mars', email: 'student@eguro.edu.ph', role: 'student', password: 'password123', createdAt: '2026-10-10' },
        { id: 6, userCode: '2026-002', name: 'Ada Lovelace', email: 'ada@eguro.edu.ph', role: 'student', password: 'password123', createdAt: '2026-10-12' }
    ];

    const DEFAULT_CLASSES = [
        { id: 1, code: '2-CS1', name: 'Web Systems and Technologies', teacherId: 3, teacherName: 'Prof. Juan Dela Cruz', semester: '1st Semester', fiscalYear: '2026-2027', studentsCount: 42 },
        { id: 2, code: '2-CS2', name: 'Database Management Systems', teacherId: 3, teacherName: 'Prof. Juan Dela Cruz', semester: '1st Semester', fiscalYear: '2026-2027', studentsCount: 38 },
        { id: 3, code: '2-CS4', name: 'Data Structures and Algorithms', teacherId: 4, teacherName: 'Prof. Sarah Connor', semester: '1st Semester', fiscalYear: '2026-2027', studentsCount: 40 }
    ];

    function init() {
        if (!localStorage.getItem('eguro_users')) {
            localStorage.setItem('eguro_users', JSON.stringify(DEFAULT_USERS));
        }
        if (!localStorage.getItem('eguro_classes')) {
            localStorage.setItem('eguro_classes', JSON.stringify(DEFAULT_CLASSES));
        }
    }

    init();

    return {
        // Users
        getUsers() {
            try {
                return JSON.parse(localStorage.getItem('eguro_users')) || DEFAULT_USERS;
            } catch (e) {
                return DEFAULT_USERS;
            }
        },

        getTeachers() {
            return this.getUsers().filter(u => u.role === 'teacher');
        },

        addUser(user) {
            const users = this.getUsers();
            user.id = Date.now();
            user.createdAt = new Date().toISOString().split('T')[0];
            users.push(user);
            localStorage.setItem('eguro_users', JSON.stringify(users));
            return user;
        },

        deleteUser(id) {
            let users = this.getUsers();
            users = users.filter(u => u.id !== Number(id));
            localStorage.setItem('eguro_users', JSON.stringify(users));
        },

        // Classes
        getClasses() {
            try {
                return JSON.parse(localStorage.getItem('eguro_classes')) || DEFAULT_CLASSES;
            } catch (e) {
                return DEFAULT_CLASSES;
            }
        },

        addClass(cls) {
            const classes = this.getClasses();
            cls.id = Date.now();
            cls.studentsCount = cls.studentsCount || 0;
            classes.push(cls);
            localStorage.setItem('eguro_classes', JSON.stringify(classes));
            return cls;
        },

        deleteClass(id) {
            let classes = this.getClasses();
            classes = classes.filter(c => c.id !== Number(id));
            localStorage.setItem('eguro_classes', JSON.stringify(classes));
        },

        // Auth
        login(identifier, password) {
            const users = this.getUsers();
            const idNorm = identifier.trim().toLowerCase();
            const match = users.find(u =>
                (u.email.toLowerCase() === idNorm || u.userCode.toLowerCase() === idNorm) &&
                u.password === password
            );

            if (match) {
                localStorage.setItem('eguro_session', JSON.stringify({
                    id: match.id,
                    userCode: match.userCode,
                    name: match.name,
                    email: match.email,
                    role: match.role
                }));
                return { success: true, user: match };
            }
            return { success: false, message: 'Invalid User ID / Email or Password' };
        },

        getCurrentUser() {
            try {
                return JSON.parse(localStorage.getItem('eguro_session')) || null;
            } catch (e) {
                return null;
            }
        },

        logout() {
            localStorage.removeItem('eguro_session');
        }
    };
})();
