# Hospital Profile Submission System

A comprehensive web-based hospital profile submission system built with PHP, PostgreSQL, and vanilla JavaScript. This system enables hospitals to submit detailed profiles including basic information, medical staff, treatments, packages, and media galleries.

## 🌟 Features

### Module 1: Hospital Profile
- **Basic Information**: Name, type, establishment year, beds, patient counts
- **Accreditations**: JCI, NABH, ISO, NABL, and custom accreditations
- **Location & Contact**: Full address, coordinates for maps, multiple contact methods
- **Descriptions**: Brief intro, detailed about, key highlights
- **Patient Reviews**: Text reviews with star ratings
- **Clinical Capabilities**: Departments, specialties, advanced equipment, facilities

### Module 2: Doctor Profiles
- Identity & credentials (name, title, qualifications)
- Education & training background
- Experience and languages spoken
- Areas of expertise and procedures performed
- Patient count and biography
- Consultation timings
- Awards, recognitions, and publications

### Module 3: Treatments
- Treatment domain and specialty
- Pricing and descriptions
- Recovery time and hospital stay duration
- Treatment-specific accreditations

### Module 4: Medical Packages
- Package details (name, tagline, rate, duration)
- Comprehensive descriptions
- Inclusions (visa assistance, flights, insurance)
- Customizable add-ons with pricing

### Module 5: Media Gallery
- Multi-image upload with drag-and-drop
- Image preview before submission
- Automatic image optimization
- Support for JPG, PNG, and WEBP formats

## 🛠️ Technology Stack

- **Backend**: PHP 8.2+
- **Database**: Neon PostgreSQL with JSONB support
- **Frontend**: HTML5, Tailwind CSS 3 (CDN), Vanilla JavaScript
- **File Upload**: AJAX-based with validation
- **Design**: Mobile-first, responsive, premium UI/UX

## 📋 Prerequisites

- PHP 8.2 or higher
- PostgreSQL database (Neon recommended)
- PDO_PGSQL extension enabled
- GD library for image optimization (optional)
- Web server (Apache/Nginx)

## 🚀 Installation

### 1. Clone or Download the Project

```bash
git clone <your-repository-url>
cd hospital-submission
```

### 2. Database Setup

Create a PostgreSQL database and run the following schema:

```sql
CREATE TABLE hospital_profiles (
  id SERIAL PRIMARY KEY,
  name VARCHAR(256) NOT NULL,
  type VARCHAR(20) NOT NULL,
  establishment_year INTEGER,
  accreditations JSONB,
  beds INTEGER,
  patient_count JSONB,
  location JSONB NOT NULL,
  contact JSONB,
  description JSONB,
  departments JSONB,
  specialties JSONB,
  equipment JSONB,
  facilities JSONB,
  doctors JSONB DEFAULT '[]',
  treatments JSONB DEFAULT '[]',
  packages JSONB DEFAULT '[]',
  photos JSONB DEFAULT '[]',
  status VARCHAR(20) DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT NOW()
);
```

### 3. Configure Database Connection

Edit `database.ini` with your Neon PostgreSQL credentials:

```ini
[database]
host = "your-endpoint.region.neon.tech"
port = "5432"
name = "your_database_name"
user = "your_username"
password = "your_password"
```

> **⚠️ IMPORTANT**: Never commit `database.ini` to version control. It's already in `.gitignore`.

### 4. Set Directory Permissions

Ensure the web server can write to the uploads directory:

```bash
mkdir uploads
chmod 755 uploads
```

### 5. Deploy to Web Server

- Upload all files to your web server
- Point your domain to the `hospital-submission` directory
- Access via browser: `https://yourdomain.com/index.php`

## 📖 Usage Guide

### Submitting a Hospital Profile

1. **Basic Info & Location** (Tab 1)
   - Fill in hospital name, type, establishment year
   - Add patient counts and accreditations
   - Provide complete address and contact details
   - Optionally add GPS coordinates for map integration

2. **Content & Capabilities** (Tab 2)
   - Write brief and detailed descriptions
   - Add key highlights (bullet points)
   - Collect patient reviews with star ratings
   - Add departments using tag input (type and press Enter)
   - Add medical specialties and equipment
   - Select available facilities

3. **Doctors** (Tab 3)
   - Click "Add Doctor" for each medical professional
   - Fill comprehensive details including bio, qualifications
   - Add expertise areas, procedures, and publications

4. **Treatments** (Tab 4)
   - Click "Add Treatment" for each procedure offered
   - Specify domain, pricing, recovery time
   - Add detailed descriptions

5. **Packages & Media** (Tab 5)
   - Create medical tourism packages
   - Add inclusions and customizable add-ons
   - Upload hospital photos (drag-and-drop or click)
   - Preview images before submission

6. **Submit**
   - Review all information
   - Click "Submit Profile"
   - System will upload images and save data
   - Success message appears on completion

## 🎨 Design Features

- **Premium UI**: Modern gradient colors, smooth animations
- **Tag Input System**: Easy-to-use tag inputs for arrays (departments, specialties)
- **Star Ratings**: Interactive star rating for patient reviews
- **Progress Bar**: Visual progress indicator across tabs
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Image Preview**: See uploaded images before submission
- **Loading States**: Visual feedback during submission
- **Toast Notifications**: Success/error messages

## 🔒 Security Features

- Server-side validation for all inputs
- XSS prevention through HTML sanitization
- MIME type validation for uploads
- File size restrictions (10MB per image)
- SQL injection prevention via PDO prepared statements
- CSRF protection ready (can be added)

## 📁 Project Structure

```
hospital-submission/
├── index.php              # Main form page
├── submit.php             # Form processor with validation
├── upload.php             # Image upload handler
├── database.ini           # Database configuration (gitignored)
├── .gitignore            # Git ignore rules
├── README.md             # This file
├── assets/
│   ├── style.css         # Premium CSS styling
│   └── script.js         # Dynamic form logic
└── uploads/              # Uploaded images (auto-created)
```

## 🧪 Testing

### Manual Testing Checklist

- [ ] Form loads correctly on all browsers
- [ ] Tab navigation works smoothly
- [ ] All input fields accept and validate data
- [ ] Tag inputs work (type and press Enter)
- [ ] Star ratings are interactive
- [ ] Image upload with drag-and-drop works
- [ ] Image preview displays correctly
- [ ] Form submission succeeds with valid data
- [ ] Error messages show for invalid data
- [ ] Success toast appears after submission
- [ ] Data saves correctly to database
- [ ] Mobile responsive design works

### Browser Compatibility

Tested on:
- Chrome 120+
- Firefox 121+
- Safari 17+
- Edge 120+
- Mobile browsers (iOS Safari, Chrome Mobile)

## 🔧 Configuration Options

### Adjust Upload Limits

Edit `upload.php`:

```php
$maxSize = 10 * 1024 * 1024; // Change to 10MB
```

### Change Image Quality

Edit `upload.php`:

```php
$quality = 90; // Increase from 85 to 90
```

### Modify Max Items

Edit `script.js`:

```javascript
if (doctors.length >= 100) { // Change from 50 to 100
```

## 🐛 Troubleshooting

### Database Connection Failed
- Verify `database.ini` credentials
- Ensure PostgreSQL server is running
- Check if PDO_PGSQL extension is enabled: `php -m | grep pdo_pgsql`

### Upload Directory Not Writable
```bash
chmod 755 uploads
chown www-data:www-data uploads  # Linux
```

### Images Not Uploading
- Check PHP upload limits in `php.ini`:
  - `upload_max_filesize = 10M`
  - `post_max_size = 20M`
- Verify GD library is installed: `php -m | grep gd`

### Cloudinary (Recommended for Vercel)

- This project uses direct uploads to Cloudinary from the browser. Configure the following Environment Variables in your Vercel project:
   - `CLOUDINARY_CLOUD_NAME`
   - `CLOUDINARY_API_KEY`
   - `CLOUDINARY_API_SECRET`

- The app requests a signed upload from the server (`/api/cloudinary_signature.php`), then uploads images directly to Cloudinary. Uploaded image URLs are saved with submissions.

- To get credentials, create an account at https://cloudinary.com/, then find your `cloud name`, `API Key`, and `API Secret` in the dashboard.
  
## Hosting on GitHub Pages + Vercel (Recommended)

- This repo can serve a static frontend via GitHub Pages and the PHP API on Vercel.
- Steps:
   1. Deploy the backend to Vercel (this repo). Set environment variables in Vercel: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET`.
   2. Note your Vercel app URL (e.g., `https://my-app.vercel.app`).
   3. Edit `index.html` at the top and set `window.API_BASE_URL = 'https://my-app.vercel.app'` so the static frontend will call the Vercel API endpoints.
   4. Enable GitHub Pages for the repository (Settings → Pages → Choose branch `main` / root). The static site will then be available at `https://<your-username>.github.io/<repo>`.

- After these steps, the site will use Cloudinary for uploads and Vercel to run the PHP APIs.

### Form Submission Fails
- Check browser console for JavaScript errors
- Verify all required fields are filled
- Check server error logs

## 📝 Database Schema Details

### JSONB Column Structures

**patient_count**:
```json
{"total": 50000, "annual": 8000}
```

**location**:
```json
{
  "address": "123 Medical St",
  "city": "Pondicherry",
  "state": "Puducherry",
  "lat": 11.934202,
  "lng": 79.830429
}
```

**contact**:
```json
{
  "general": "+91 123456789",
  "emergency": "+91 987654321",
  "email": "info@hospital.com",
  "website": "https://hospital.com"
}
```

**doctors** (array):
```json
[{
  "name": "Dr. John Smith",
  "title": "Cardiologist",
  "qualification": "MBBS, MD, DM",
  "experience": "15",
  "languages": "English, Hindi",
  "expertise": "Heart Surgery, Angioplasty",
  "patientsCount": "5000",
  "about": "Detailed biography...",
  "timing": "Mon-Fri: 9AM-5PM"
}]
```

## 🚀 Future Enhancements

- Admin dashboard to view/approve submissions
- Email notifications on new submissions
- Export functionality (CSV/PDF)
- Multi-language support
- Payment gateway integration for packages
- Doctor profile images
- Treatment before/after photo galleries
- Patient testimonial videos
- Advanced analytics dashboard

## 📄 License

This project is provided as-is for educational and commercial use.

## 👥 Support

For issues or questions:
1. Check the troubleshooting section
2. Review error logs
3. Contact your development team

## 🙏 Credits

Built with:
- [Tailwind CSS](https://tailwindcss.com/)
- [Neon PostgreSQL](https://neon.tech/)
- Modern web standards

---

**Version**: 1.0.0  
**Last Updated**: December 2025  
**Status**: Production Ready ✅

Redeploy trigger: 2025-12-14T01:54:00Z
