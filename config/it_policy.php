<?php

/*
|--------------------------------------------------------------------------
| Company IT Policy (CFI-IT-POL-001)
|--------------------------------------------------------------------------
|
| Content shown on the "IT Policy" page. When the policy is revised, edit the
| text here (and replace resources/documents/CFI-IT-POL-001.pdf) — no view
| changes are needed. Leave a meta value as null to show "Not set".
|
| Section types:
|   list   => 'items'  : bullet points
|   text   => 'body'   : a paragraph
|   groups => 'groups' : labelled sets of chips (label + items)
|
*/

return [

    'title' => 'Company Information Technology (IT) Policy',
    'company' => 'Crest Forwarder Inc.',
    'pdf' => 'CFI-IT-POL-001.pdf',

    'meta' => [
        'Document No.' => 'CFI-IT-POL-001',
        'Version' => '1.0',
        'Effective Date' => 'September 25, 2026',
        'Review Date' => 'September 23, 2026',
        'Policy Owner' => 'IT Department / Management',
        'Approved By' => 'HR & IT Department',
    ],

    // "Key rules at a glance" — each links to the section it comes from.
    'highlights' => [
        ['icon' => 'lock',   'title' => 'Lock your computer',      'text' => 'Lock it whenever you step away.',                                  'section' => 3],
        ['icon' => 'key',    'title' => 'Never share passwords',   'text' => 'Use your own account, with strong and unique passwords.',          'section' => 3],
        ['icon' => 'shield', 'title' => 'Report suspicious MFA',   'text' => 'Tell IT about any MFA request you did not expect.',                'section' => 4],
        ['icon' => 'box',    'title' => 'Authorized software only', 'text' => 'No pirated software, unauthorized VPNs or remote-access tools.', 'section' => 5],
        ['icon' => 'mail',   'title' => 'Think before you click',  'text' => 'Report phishing, malware and accidental data disclosure.',         'section' => 6],
        ['icon' => 'alert',  'title' => 'Report incidents at once', 'text' => 'Lost devices, malware, data leaks or anything suspicious.',      'section' => 11],
    ],

    'sections' => [
        [
            'number' => 1,
            'title' => 'Purpose',
            'type' => 'text',
            'body' => 'Protect company information, systems, devices, and networks; maintain secure and reliable IT operations; prevent unauthorized access and cyber threats.',
        ],
        [
            'number' => 2,
            'title' => 'Scope',
            'type' => 'groups',
            'groups' => [
                [
                    'label' => 'Applies to',
                    'items' => ['Employees', 'Managers', 'Contractors', 'Interns', 'Third parties', 'Company IT equipment', 'Authorized personal devices'],
                ],
                [
                    'label' => 'Covers',
                    'items' => ['Computers', 'Microsoft 365 / Entra ID / Intune', 'Email', 'Networks', 'ERP', 'Cloud systems', 'Backups', 'CCTV'],
                ],
            ],
        ],
        [
            'number' => 3,
            'title' => 'User Accounts & Access',
            'type' => 'list',
            'items' => [
                'Individual accounts are required.',
                'Passwords must not be shared.',
                'Computers must be locked when unattended.',
                'Access is granted according to job responsibilities.',
                'Employee access must be removed immediately upon separation.',
            ],
        ],
        [
            'number' => 4,
            'title' => 'Password & MFA',
            'type' => 'list',
            'items' => [
                'Use strong and unique passwords.',
                'MFA is required for Microsoft 365, Entra ID, VPN, administrators, and critical systems.',
                'Suspicious MFA requests must be reported to IT.',
            ],
        ],
        [
            'number' => 5,
            'title' => 'Computers & Software',
            'type' => 'list',
            'items' => [
                'Company devices are primarily for business use.',
                'Only authorized software may be installed.',
                'Pirated/cracked software, unauthorized VPNs, remote-access tools, and security bypasses are prohibited.',
                'Devices must have security updates, antivirus/EDR, and appropriate protection.',
            ],
        ],
        [
            'number' => 6,
            'title' => 'Email & Internet',
            'type' => 'list',
            'items' => [
                'Be cautious with suspicious links, attachments, payment requests, and password-reset messages.',
                'Report phishing, malware, account compromise, and accidental data disclosure.',
                'Illegal activities, hacking, piracy, malware distribution, and bypassing security controls are prohibited.',
            ],
        ],
        [
            'number' => 7,
            'title' => 'Network & Remote Access',
            'type' => 'list',
            'items' => [
                'Corporate, guest, and IoT/CCTV networks should be separated.',
                'Remote access must use approved secure methods, including VPN and MFA where required.',
                'Unsecured Wi-Fi should not be used for sensitive work.',
            ],
        ],
        [
            'number' => 8,
            'title' => 'Data Protection',
            'type' => 'list',
            'items' => [
                'Company information must be classified as Public, Internal, Confidential, or Highly Confidential/Restricted.',
                'Sensitive information must only be accessed by authorized personnel.',
                'Company data should not be stored in personal email, personal cloud storage, unauthorized USB drives, or personal computers.',
            ],
        ],
        [
            'number' => 9,
            'title' => 'Microsoft 365 / Entra ID / Intune',
            'type' => 'text',
            'body' => 'Where implemented, the company should use centralized identity management, MFA, Conditional Access, Intune, device compliance, SSO, security monitoring, and least-privilege access.',
        ],
        [
            'number' => 10,
            'title' => 'Backup & Cybersecurity',
            'type' => 'text',
            'body' => 'Critical systems and data must be backed up regularly. Backups should be protected and, where possible, include isolated/offline copies. IT should maintain firewalls, endpoint security, patching, MFA, network segmentation, monitoring, and vulnerability management.',
        ],
        [
            'number' => 11,
            'title' => 'Security Incidents',
            'type' => 'text',
            'body' => 'Employees must immediately report phishing, ransomware, malware, unauthorized access, lost devices, data leakage, and suspicious activity. IT will investigate, contain, recover, document, and implement corrective actions.',
            'cta' => true, // shows the "Report to IT" button
        ],
        [
            'number' => 12,
            'title' => 'IT Asset Management',
            'type' => 'text',
            'body' => 'IT maintains an inventory of computers, laptops, mobile devices, servers, network equipment, CCTV, software licenses, and cloud services. Equipment must not be transferred or disposed of without authorization.',
        ],
        [
            'number' => 13,
            'title' => 'IT Changes & Support',
            'type' => 'text',
            'body' => 'Major changes to firewalls, servers, ERP, networks, Microsoft 365, DNS, email, and software must be authorized, documented, and reviewed. IT incidents are prioritized from P1 Critical to P4 Low based on business impact.',
        ],
        [
            'number' => 14,
            'title' => 'Third-Party & Administrator Access',
            'type' => 'text',
            'body' => 'Vendor access must be authorized, limited, preferably time-bound, protected by MFA, documented, and revoked when no longer required. Administrative privileges are restricted to authorized IT personnel.',
        ],
        [
            'number' => 15,
            'title' => 'Business Continuity',
            'type' => 'text',
            'body' => 'The company must maintain recovery procedures for hardware failure, ransomware, internet/power outages, natural disasters, cloud disruptions, and data corruption. Recovery procedures should be periodically tested.',
        ],
        [
            'number' => 16,
            'title' => 'Employee & IT Responsibilities',
            'type' => 'text',
            'body' => 'Employees must protect company information, devices, passwords, and follow IT/security procedures. IT is responsible for infrastructure, cybersecurity, accounts, devices, backups, support, monitoring, and documentation. Management provides resources and approves major IT investments.',
        ],
        [
            'number' => 17,
            'title' => 'Violations & Review',
            'type' => 'text',
            'body' => 'Violations may result in warnings, removal of IT access, training, disciplinary action, termination, or legal action depending on severity. The policy should be reviewed at least annually or when significant IT/business changes occur.',
        ],
    ],

    'summary' => 'Crest Forwarder Inc.\'s IT Policy establishes rules for protecting company data, systems, devices, networks, and users. It requires secure accounts and passwords, MFA, authorized software, protected networks, controlled access, regular backups, cybersecurity monitoring, incident reporting, asset management, and proper IT change management. Employees are responsible for following security rules, while IT manages infrastructure, security, support, backups, and access. Management provides oversight and resources. Policy violations may result in disciplinary action.',

    'acknowledgement' => [
        'intro' => 'Every employee is asked to sign the IT Policy Acknowledgement and Acceptance Form (included in the PDF). By signing, you acknowledge that you have received, read, and understood the policy, and you agree to:',
        'items' => [
            'Follow all applicable IT and cybersecurity policies and procedures.',
            'Protect company information, systems, devices, and accounts.',
            'Keep my passwords and authentication information confidential.',
            'Use company IT resources responsibly and primarily for legitimate business purposes.',
            'Not install unauthorized, pirated, or prohibited software.',
            'Report phishing, malware, suspicious activity, lost equipment, unauthorized access, or possible data breaches immediately to IT.',
            'Follow IT instructions regarding security, access, devices, and company systems.',
            'Protect confidential company, employee, and customer information.',
            'Return company IT equipment when required.',
            'Cooperate with IT security investigations and procedures.',
        ],
    ],
];
