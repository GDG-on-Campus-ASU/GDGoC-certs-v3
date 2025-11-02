<?php

namespace Database\Seeders;

use App\Models\DocumentationPage;
use Illuminate\Database\Seeder;

class DocumentationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentationPage::create([
            'title' => 'Getting Started',
            'slug' => 'getting-started',
            'content' => '# Welcome to GDGoC Certificate Platform!

This guide will walk you through the basics of using the GDGoC Certificate Generation Platform.

## What is this platform?

The GDGoC Certificate Generation Platform allows you to easily create, manage, and distribute digital certificates for your events and activities.

## Key Features

- **Certificate Templates**: Create custom certificate designs
- **Bulk Generation**: Generate multiple certificates at once using CSV uploads
- **Email Distribution**: Automatically send certificates to recipients
- **Certificate Validation**: Public validation system for certificate authenticity
- **SMTP Configuration**: Use your own email provider

## Getting Started

1. Set up your certificate template
2. Configure your SMTP provider for email delivery
3. Generate certificates for your event
4. Distribute certificates to recipients

For detailed information on each feature, please refer to the specific documentation pages in the left sidebar.',
            'order' => 1,
        ]);

        DocumentationPage::create([
            'title' => 'Bulk Upload (CSV)',
            'slug' => 'bulk-upload',
            'content' => '# How to use Bulk Upload

The bulk upload feature allows you to generate multiple certificates at once by uploading a CSV file.

## Step 1: Download the CSV Template

**[Download CSV Template](/templates/bulk-certificates-template.csv)**

Click the link above to download a ready-to-use CSV template with sample data.

## Step 2: Edit the CSV File

You can edit the CSV file using:

### Using Google Sheets

1. Go to [Google Sheets](https://sheets.google.com)
2. Click **File > Import** and upload the template
3. Edit the data in the spreadsheet
4. Replace the sample data with your actual recipient information
5. When done, click **File > Download > Comma Separated Values (.csv)**
6. Save the file to your computer

### Using Microsoft Excel

1. Open Microsoft Excel
2. Click **File > Open** and select the downloaded template
3. Edit the data in the spreadsheet
4. Replace the sample data with your actual recipient information
5. When done, click **File > Save As**
6. Choose **CSV (Comma delimited) (*.csv)** as the file type
7. Save the file to your computer

### CSV File Format

Your CSV file must include the following headers in this exact order:

```
recipient_name,recipient_email,state,event_type,event_title,issue_date
```

#### Column Descriptions

- **recipient_name**: Full name of the certificate recipient (required)
- **recipient_email**: Email address of the recipient (optional, leave blank if not sending via email)
- **state**: Certificate state - must be either `attending` or `completing` (required)
- **event_type**: Type of event - must be either `workshop` or `course` (required)
- **event_title**: Name of the event or course (required)
- **issue_date**: Date the certificate was issued in YYYY-MM-DD format (required)

### Example CSV Content

```
recipient_name,recipient_email,state,event_type,event_title,issue_date
John Doe,john@example.com,completing,workshop,Web Development Workshop,2024-01-15
Jane Smith,jane@example.com,attending,course,Mobile App Development Course,2024-01-20
Alice Johnson,alice@example.com,completing,workshop,Python Programming,2024-01-25
```

**Important:** Each row represents one certificate recipient.

## Step 3: Upload Your CSV File

1. Navigate to **Dashboard > Bulk Certificates**
2. Select your certificate template
3. Click **Choose File** and select your edited CSV file
4. Click **Generate Certificates**

## Step 4: Review and Send

After generation, you can:
- Review all generated certificates
- Send certificates via email
- Download certificates as PDF files
- Revoke certificates if needed

## Important Notes

- Make sure all email addresses are valid (or leave blank if not sending via email)
- **State** must be exactly `attending` or `completing` (case-sensitive)
- **Event Type** must be exactly `workshop` or `course` (case-sensitive)
- **Issue Date** must be in YYYY-MM-DD format (e.g., 2024-01-15)
- The event title will be used in the certificate
- Certificates are generated immediately upon upload
- You can track all certificates in the **Certificates** section
- Do not change the header row (first row) in the CSV file
- Save the file as CSV format, not Excel format (.xlsx)',
            'order' => 2,
        ]);

        DocumentationPage::create([
            'title' => 'Creating Certificate Templates',
            'slug' => 'certificate-templates',
            'content' => "# Creating Certificate Templates

Certificate templates define how your certificates will look when generated.

## Creating a New Template

1. Navigate to **Dashboard > Templates > Certificates**
2. Click **New Certificate Template**
3. Fill in the required information:
   - **Template Name**: Internal name for your template
   - **Certificate Design**: Upload or design your certificate

## Template Variables

You can use the following variables in your template:

- `{{recipient_name}}` - The recipient's name
- `{{event_title}}` - The event or course title
- `{{issue_date}}` - The date the certificate was issued
- `{{unique_id}}` - A unique identifier for validation

## Design Tips

- Use high-resolution images (300 DPI recommended)
- Include your organization logo
- Make sure text is readable
- Test with sample data before bulk generation

## Cloning Templates

You can clone existing templates to create variations:
1. Go to the template list
2. Click **Clone** on any template
3. Modify as needed",
            'order' => 3,
        ]);

        DocumentationPage::create([
            'title' => 'SMTP Configuration',
            'slug' => 'smtp-configuration',
            'content' => '# SMTP Configuration

To send certificates via email, you need to configure an SMTP provider.

## Supported Providers

- Gmail
- Outlook
- SendGrid
- Mailgun
- Any custom SMTP server

## Setup Steps

1. Navigate to **Dashboard > SMTP Providers**
2. Click **New SMTP Provider**
3. Enter your SMTP details:
   - **Provider Name**: A name to identify this provider
   - **Host**: SMTP server address
   - **Port**: Usually 587 for TLS or 465 for SSL
   - **Username**: Your email or SMTP username
   - **Password**: Your email password or SMTP password
   - **Encryption**: Choose TLS or SSL

## Gmail Setup

For Gmail accounts:
1. Enable 2-factor authentication
2. Generate an app-specific password
3. Use `smtp.gmail.com` as host
4. Use port `587` with TLS encryption

## Testing Your Configuration

After saving:
1. Use the **Test Connection** button
2. Check if the test email arrives
3. Adjust settings if needed

## Security Note

Your SMTP credentials are encrypted and stored securely.',
            'order' => 4,
        ]);
    }
}
