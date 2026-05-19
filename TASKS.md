
---

```markdown



# SM MAS Project - Team Tasks & Guidelines

Project: Smart Medicine Monitoring & Alert System for
Kyambogo Medical Centre

## 👥 Team Members &
Assignments

| Name | Role | Branch Name | Files to Work On |

|------|------|-------------|------------------|

| **Leonard (Team Lead)** | Database & Core Setup |
`feature/database-setup` | `config/database.php`, `install.php`,
`fresh_install.php`, `fix_admin.php`, `fix_user_passwords.php` |


| **Provia** | Authentication & Security |
`feature/authentication` | `login.php`, `logout.php`, `simple_login.php`,
`debug_login.php`, `includes/auth.php`, `includes/encryption.php` |


| **Harriet** | Dashboard & Navigation |
`feature/dashboard-ui` | `dashboard.php`, `enter.php`, `index.html`,
`includes/navbar.php` |


| **Eliphaz** | Medicine Inventory | `feature/medicine-mgmt`
| `medicine.php`, `batch.php`, `alert.php` |


| **Jordan** | Patient Management | `feature/patient-mgmt` |
`patient.php`, `transaction.php`, `users.php` |

| **Gilbert** | Reports Module | `feature/reports` |
`reports.php`, `reports/alerts_report.php`, `reports/dashboard_report.php`,
`reports/expiry_report.php`, `reports/patients_report.php`,
`reports/stock_report.php`, `reports/transactions_report.php` |


| **Ibrah** | CSS Styling | `feature/css-styling` |
`css/style.css` |

| **Stanley** | Testing & QA | `feature/testing` | Test
all features, review pull requests, write test reports |




## 📁 Folder Structure



 



```

C:\wamp64\www\smms

├── .github/

│   └── CODEOWNERS
(optional)

├── config/

│   └── database.php

├── css/

│   └── style.css

├── includes/

│   ├──
auth.php

│   ├──
encryption.php

│   └── navbar.php

├── reports/

│   ├──
alerts_report.php

│   ├──
dashboard_report.php

│   ├──
expiry_report.php

│   ├──
patients_report.php

│   ├──
stock_report.php

│   └──
transactions_report.php

├── alert.php

├── batch.php

├── dashboard.php

├── debug_login.php

├── enter.php

├── fix_admin.php

├── fix_user_passwords.php

├── fresh_install.php

├── index.html

├── install.php

├── login.php

├── logout.php

├── medicine.php

├── patient.php

├── reports.php

├── simple_login.php

├── transaction.php

└── users.php

```



 



---



 



## ⚠️ Important Rules Everyone Must
Follow



 



### Rule 1: Never Work on Main Branch



Always create your own branch before making any changes.



 



```bash



git checkout -b feature/your-role-name



```

Rule 2: Only Edit Your Assigned Files

Do NOT edit files assigned to other team members. If you
need to discuss a change, talk to that person first.

Rule 3: Pull Before You Push

Always get the latest changes before pushing your work.

```bash



git checkout main

git pull origin main

git checkout feature/your-branch

git merge main



```

Rule 4: Write Meaningful Commit Messages

Bad: fixed stuff

Good: Added validation to login form

Rule 5: Test Locally Before Pushing

Always test your changes at http://localhost/smms/ before
pushing to GitHub.

Rule 6: Use Pull Requests

Never push directly to main. Always create a Pull Request
and have at least one person review it.

---

🛠️ Setup Instructions for
Each Team Member

Step 1: Install Required Software

Software Download Link Why You Need It

WampServer https://www.wampserver.com Runs PHP and MySQL
locally

VS Code https://code.visualstudio.com Write your code

Git https://git-scm.com Clone and push code to GitHub

Step 2: Accept GitHub Invitation

Check your email for an invitation from GitHub and click
"Accept Invitation".

Step 3: Clone the Repository

Open VS Code → Open terminal (Ctrl + `) → Run:

```bash



cd C:\wamp64\www\

git clone https://github.com/INSIGHT-TEAM-GROUP-SIX/smmas-project.git

cd smmas

code .



```

Step 4: Create Your Branch

In VS Code terminal, run:

```bash



git checkout -b feature/your-role-name



```

Replace your-role-name with your assigned branch from the
table above.

Step 5: Start Coding

Edit ONLY your assigned files. Test at
http://localhost/smms/

Step 6: Save and Push Your Work

```bash



git add .

git commit -m "description of what you did"

git push origin feature/your-role-name



```

Step 7: Create a Pull Request

Go to GitHub → Your repository → Pull Requests → New Pull
Request → Select your branch → Create Pull Request

---

📅 Daily Workflow

Every day when you start working:

```bash


# 1. Open VS Code with your project

cd C:\wamp64\www\smmas

code .

# 2. Get latest changes

git checkout main

git pull origin main

# 3. Switch to your branch

git checkout feature/your-role-name

# 4. Merge main into your branch

git merge main

# 5. Write code, test, repeat...

# 6. End of day - push your work

git add .

git commit -m "describe today's work"

git push origin feature/your-role-name



```

---

🚀 Hosting on Render or
Railway (For Final Presentation)

Once your project is complete, follow these steps to host it
online.

Option A: Host on Render (Recommended - Has Free Tier)

Step 1: Go to https://render.com

Step 2: Sign up with GitHub

Step 3: Click "New +" → "Web Service"

Step 4: Connect your GitHub repository

Step 5: Fill in:

Setting Value

Name smms

Environment PHP

Build Command composer install (leave empty if no composer)

Start Command cp -r . /opt/render/project/src/ &&
apache2-foreground

Step 6: Click "Create Web Service"

For Database: Click "New +" →
"PostgreSQL" → Create free database → Copy connection details to your
config/database.php

Option B: Host on Railway (Also Has Free Tier)

Step 1: Go to https://railway.app

Step 2: Sign up with GitHub

Step 3: Click "New Project" → "Deploy from
GitHub repo"

Step 4: Select your smms repository

Step 5: Railway auto-detects PHP (it just works!)

For Database: Click "New" → "Database" →
"MySQL" → It automatically connects

---

✅ Project Completion Checklist

Before the final presentation, ensure:

· All 8 team members have pushed their code

· All pull requests have been reviewed and merged

· The site works on http://localhost/smms/

· The site works on the hosted URL (Render or Railway)

· No broken links or missing images

· All forms submit correctly

· Reports generate properly

· Login/logout works for all user types

· Mobile responsive (check on phone or resize browser)

---

📞 Communication

Use your group WhatsApp/Discord channel for:

· Daily quick updates (5 minutes)

· Sharing pull request links for review

· Asking for help with merge conflicts

· Reporting bugs found during testing

---

🎯 Summary Card

Action Command

Open project cd C:\wamp64\www\smmas && code .

Create branch git checkout -b feature/your-name

Check status git status

Save & commit git add . && git commit -m
"message"

Push git push origin feature/your-name

Get updates git pull origin main

Test locally http://localhost/smmas/

---

Good luck team! Let's build something great together. 

---

## 📤 How to Add This to Your

Project

1. In VS Code, open your `smmas` folder
2. Create a new file called `TASKS.md`
3. Copy EVERYTHING from the code block above
4. Save the file (Ctrl + S)
5. Run these commands:

```bash


git add TASKS.md

git commit -m "Add team tasks and guidelines"

git push origin main
```

📝 What You Need to Change

One thing you need to update: In Step 3 of "Setup
Instructions", replace YourOrganizationName with your actual GitHub
organization name.

Example:

```bash



git clone https://github.com/INSIGHT-TEAM-GROUP-SIX/smmas-project.git



```


# Smart Medicine Monitoring & Alert System

## About

A system for Kyambogo Medical Centre to monitor medicine
stock levels, track expiries, and send alerts when stock runs low.

## Features

- Medicine inventory management
- Patient records tracking
- Stock expiry alerts
- Reports generation
- User authentication

## Technologies

- PHP
- MySQL
- HTML/CSS
- JavaScript

## Setup

1. Clone repository to `C:\wamp64\www\`
2. Import database from `install.php`
3. Access at `http://localhost/smmas/`

## Team Members

- Leonard (Lead)
- Provia
- Harriet
- Eliphaz
- Jordan
- Gilbert
- Ibrah
- Stanley
