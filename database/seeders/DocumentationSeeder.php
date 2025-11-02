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

## Step 1: Prepare Your CSV File

Your CSV file must include the following headers:

```
recipient_name,recipient_email,event_title
```

### Example CSV Content

```
John Doe,john@example.com,Web Development Workshop
Jane Smith,jane@example.com,Web Development Workshop
Alice Johnson,alice@example.com,Mobile App Development
```

## Step 2: Download the Template

1. Navigate to **Dashboard > Bulk Certificates**
2. Click on the **Download CSV Template** link
3. Fill in your recipient data

## Step 3: Upload Your CSV File

1. Go to **Dashboard > Bulk Certificates**
2. Select your certificate template
3. Choose your CSV file
4. Click **Generate Certificates**

## Step 4: Review and Send

After generation, you can:
- Review all generated certificates
- Send certificates via email
- Download certificates as PDF files
- Revoke certificates if needed

## Important Notes

- Make sure all email addresses are valid
- The event title will be used in the certificate
- Certificates are generated immediately upon upload
- You can track all certificates in the **Certificates** section',
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
