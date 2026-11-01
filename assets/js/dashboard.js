const student = {
    id: "2026-001",
    name: "Bruno",
    fiscalYear: "2026-2027",
    semester: "1st Semester"
};

const tasks = [
    { title: "Activity 1: HTML Tables",         kind: "activity",   status: "done" },
    { title: "Quiz 3: JavaScript Events",       kind: "quiz",       status: "due-today" },
    { title: "Activity 5: Input Validation",    kind: "activity",   status: "assigned" },
    { title: "Activity 6: DOM Manipulation",    kind: "activity",   status: "assigned" },
    { title: "Quiz 2: CSS Layout",              kind: "quiz",       status: "missed" },
    { title: "Prelim Assessment",               kind: "assessment", status: "missed" },
    { title: "Midterm Assessment",              kind: "assessment", status: "due-today" },
    { title: "Finals Project Assessment",       kind: "assessment", status: "assigned" }
];


const lessons = [
    { title: "Lesson 5: CSS Layout",                           read: true },
    { title: "Lesson 7: Objects, Properties, Operators",       read: true },
    { title: "Lesson 8: Arrays, Loops, Conditionals",          read: true },
    { title: "Lesson 9: Event, Style, and Form Validation",    read: false },
    { title: "Finals Review Guide",                            read: false }
];


const bulletins = [
    { title: "INFO BULLETIN 1", age: "9 days ago",   link: "bulletin-1.html" },
    { title: "INFO BULLETIN 2", age: "9 days ago",   link: "bulletin-2.html" },
    { title: "INFO BULLETIN 3", age: "3 months ago", link: "bulletin-3.html" },
    { title: "INFO BULLETIN 4", age: "3 months ago", link: "bulletin-4.html" }
];





function setText(id, text) {
    let element = document.getElementById(id);

    if (element !== null) {
        element.textContent = text;
    }
}


function showStudentInfo() {
    setText("headerStudentName", student.name + " - STUDENT");
    setText("studentId", student.id);
    setText("navStudentName", student.name);
    setText("fiscalYearText", "Fiscal Year: " + student.fiscalYear + " / " + student.semester);
}



function showCounts() {
    let dueTodayAQ = 0;
    let assignedAQ = 0;
    let missed = 0;
    let dueTodayAssess = 0;
    let assignedAssess = 0;
    let unreadLessons = 0;

    for (let i = 0; i < tasks.length; i++) {
        let task = tasks[i];
        let isAssessment = (task.kind === "assessment");

        if (task.status === "missed") {
            missed++;
        } else if (task.status === "due-today") {
            if (isAssessment) {
                dueTodayAssess++;
            } else {
                dueTodayAQ++;
            }
        } else if (task.status === "assigned") {
            if (isAssessment) {
                assignedAssess++;
            } else {
                assignedAQ++;
            }
        }
    }

    for (let j = 0; j < lessons.length; j++) {
        if (lessons[j].read === false) {
            unreadLessons++;
        }
    }

    setText("countDueTodayAQ", dueTodayAQ);
    setText("countAssignedAQ", assignedAQ);
    setText("countMissed", missed);
    setText("countDueTodayAssess", dueTodayAssess);
    setText("countAssignedAssess", assignedAssess);
    setText("countUnreadLessons", unreadLessons);
}



function showBulletins() {
    let bulletinBox = document.getElementById("bulletinList");

    if (bulletinBox !== null) {
        let output = "";

        for (let i = 0; i < bulletins.length; i++) {
            output += "<article>";
            output += '<h4><a href="' + bulletins[i].link + '">' + bulletins[i].title + "</a></h4>";
            output += "<p>" + bulletins[i].age + "</p>";
            output += "</article>";
        }

        bulletinBox.innerHTML = output;
    }
}


showStudentInfo();
showCounts();
showBulletins();
