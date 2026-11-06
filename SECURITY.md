# Security Policy

## Supported Versions

Security fixes are released for the latest release of UnoPim Master Data
Management, developed on the `main` branch. Upgrade to the latest release to
receive them.

The package runs on UnoPim, so it is only supported on a UnoPim line that
itself still receives security fixes — see the
[UnoPim Security Policy](https://github.com/unopim/unopim/blob/3.x/SECURITY.md).

| Package version | UnoPim | PHP required | Supported |
| --------------- | ------ | ------------ | --------- |
| Latest release  | 3.x    | 8.4.1+       | Yes       |
| Older releases  | —      | —            | No        |

---

## Reporting a Vulnerability

Report security issues privately. Do **not** open a public GitHub
issue.

**Email** — [support@webkul.com](mailto:support@webkul.com).

Please include:

- A description of the issue
- Steps to reproduce (and a PoC, if available)
- Impact assessment (CVSS 4.0 or 3.1 vector welcome)
- Affected version(s) of this package and of UnoPim
- A suggested fix, if any

We acknowledge receipt within **72 hours**.

A vulnerability in UnoPim itself rather than in this package is handled under
the [UnoPim Security Policy](https://github.com/unopim/unopim/blob/3.x/SECURITY.md);
report it the same way.

---

## Security Process

1. **Acknowledgement** within 72 hours
2. **Triage** — reproduction and severity assessment
3. **Fix development** on `main`
4. **Patched release** published as a new tag
5. **GitHub Security Advisory** published with the reporter credited
   (when the reporter consents)

We aim to ship patches within **14 days** of acknowledgement for high
or critical severity, and **30 days** for medium severity.

---

## Published Advisories

Fixed vulnerabilities are announced as
[GitHub Security Advisories](https://github.com/unopim/master-data-management/security/advisories)
and listed in the [changelog](CHANGELOG.md) of the release that fixes
them. None have been published yet.

---

## Preferred Language

Reports in **English** are easiest for our team to triage quickly.

---

## Thank You

Responsible disclosure keeps UnoPim and its users safe. Thank you for
taking the time to report issues to us privately.
