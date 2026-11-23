export const projects = [
    {
        id: 'direkap',
        title: 'Direkap',
        category: 'SaaS / Financial Technology',
        description:
            'Users record transactions by sending a WhatsApp message. Direkap organizes the entries and shows their balance without a spreadsheet.',
        url: "https://rabelmaker.tech/",
        image: "/assets/editorial/direkap-home.webp",
        alt: "Direkap homepage introducing transaction recording through WhatsApp",
        caption: "Direkap \u00b7 Public product website",
    },
    {
        id: 'office-management',
        title: 'Office Management System',
        client: 'PT Apik Baraya Sejahtera',
        category: 'Enterprise Software',
        description:
            'A centralized office management platform designed to support business administration, project coordination, document management, and operational workflows.',
        url: "https://apikbaraya.com/",
        image: "/assets/editorial/office-cms.webp",
        alt: "Dashboard Office CMS showing active projects, invoices, and the project timeline",
        caption: "Office CMS dashboard",
    },
    {
        id: 'akbar-spices-website',
        title: 'Akbar Spices International Website',
        category: 'Corporate Website / Export Business',
        description:
            'A digital company presentation platform introducing Indonesian export commodities and supporting international business inquiries.',
        url: "https://akbarspices.com/",
        image: "/assets/editorial/akbarspices.com.webp",
        alt: "Akbar Spices International homepage presenting Indonesian spices for export",
        caption: "Akbar Spices \u00b7 Export website",
    },
    {
        id: 'air-quality',
        title: 'IoT Air Quality Monitoring',
        category: 'IoT / Environmental Technology',
        description:
            'An IoT-based monitoring application designed to track PM2.5 and PM10 particulate concentrations through sensor integration and a digital monitoring dashboard.',
        url: null,
        image: "/assets/editorial/aqms.webp",
        alt: "Outdoor air monitoring station with a sampling inlet and weatherproof instrument enclosure",
        caption: "Editorial illustration",
    },
] as const;
