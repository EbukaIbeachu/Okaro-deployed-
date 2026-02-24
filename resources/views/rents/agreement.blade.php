<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenancy Agreement - {{ $rent->tenant->full_name }}</title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            line-height: 1.5;
            font-size: 10pt;
            margin: 0;
            padding: 40px;
            color: #000;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            text-transform: uppercase;
            text-decoration: underline;
            font-size: 14pt;
            margin-bottom: 30px;
        }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .section { margin-bottom: 15px; text-align: justify; }
        .clause-list { list-style-type: decimal; padding-left: 20px; }
        .clause-list li { margin-bottom: 10px; }
        .sub-clause { list-style-type: lower-alpha; padding-left: 20px; }
        .signature-section { margin-top: 50px; page-break-inside: avoid; }
        .signature-row { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature-box { width: 45%; border-top: 1px solid #000; padding-top: 5px; }
        .witness-box { width: 45%; padding-top: 5px; text-align: left; }
        .print-btn {
            position: fixed; top: 20px; right: 20px;
            padding: 10px 20px; background: #7c3aed; color: white;
            border: none; cursor: pointer; border-radius: 5px;
            font-family: sans-serif;
            z-index: 1000;
        }
        .back-btn {
            position: fixed; top: 20px; left: 20px;
            padding: 10px 20px; background: #6b7280; color: white;
            border: none; cursor: pointer; border-radius: 5px;
            font-family: sans-serif;
            text-decoration: none;
            z-index: 1000;
        }
        @media print {
            .print-btn, .back-btn, .upload-section { display: none; }
            body { padding: 20px; }
        }
        .upload-section {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px dashed #ccc;
        }
    </style>
    <!-- Include html2pdf.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            // Select the container element directly to avoid body padding/margin issues
            const element = document.querySelector('.container');
            
            const opt = {
                margin:       [10, 5, 10, 0], // Reduced left margin to 0 to shift content left
                filename:     'Tenancy_Agreement_{{ str_replace(" ", "_", $rent->tenant->full_name) }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { 
                    scale: 2, 
                    useCORS: true, 
                    windowWidth: 800, // Force desktop width to avoid mobile layout wrapping
                    scrollY: 0 // Reset scroll position to prevent empty top pages
                },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
            };

            // Use html2pdf's worker
            html2pdf().set(opt).from(element).save();
        }
    </script>
</head>
<body>
    <a href="javascript:history.back()" class="back-btn" data-html2canvas-ignore="true">Back</a>
    <button onclick="window.print()" class="print-btn" data-html2canvas-ignore="true">Print Agreement</button>

    <div class="container">
    <h1>TENANCY AGREEMENT</h1>

    <div class="section">
        THIS TENANCY AGREEMENT is made this <span class="bold">{{ now()->format('jS') }}</span> day of <span class="bold">{{ now()->format('F, Y') }}</span>.
    </div>

    <div class="section">
        <div class="bold text-center uppercase" style="margin: 20px 0;">BETWEEN</div>
    </div>

    <div class="section">
        <span class="bold uppercase">OKARO PROPERTY MANAGEMENT</span> (Acting as Attorney/Agent to the Landlord) of {{ $rent->unit->building->address }} (hereinafter referred to as <span class="bold">"THE LANDLORD"</span> which expression shall where the context so admits include its successors-in-title and assigns) of the <span class="bold">ONE PART</span>.
    </div>

    <div class="section">
        <div class="bold text-center uppercase" style="margin: 20px 0;">AND</div>
    </div>

    <div class="section">
        <span class="bold uppercase">{{ $rent->tenant->full_name }}</span> of {{ $rent->unit->building->address }} (hereinafter referred to as <span class="bold">"THE TENANT"</span> which expression shall where the context so admits include his/her heirs, personal representatives, and assigns) of the <span class="bold">OTHER PART</span>.
    </div>

    <div class="section">
        <span class="bold">WHEREAS:</span>
        <ol class="clause-list">
            <li>The Landlord is the beneficial owner of the property known as <span class="bold">{{ $rent->unit->building->name }}, Unit {{ $rent->unit->unit_number }}</span> situate at <span class="bold">{{ $rent->unit->building->address }}</span> (hereinafter referred to as <span class="bold">"THE DEMISED PREMISES"</span>).</li>
            <li>The Landlord has agreed to let and the Tenant has agreed to take the Demised Premises for a term of <span class="bold">{{ \Carbon\Carbon::parse($rent->start_date)->diffInMonths(\Carbon\Carbon::parse($rent->end_date)) }} Months / 1 Year</span> commencing on <span class="bold">{{ \Carbon\Carbon::parse($rent->start_date)->format('jS F, Y') }}</span> and ending on <span class="bold">{{ \Carbon\Carbon::parse($rent->end_date)->format('jS F, Y') }}</span>.</li>
        </ol>
    </div>

    <div class="section">
        <span class="bold">NOW THIS AGREEMENT WITNESSETH AS FOLLOWS:</span>
    </div>

    <div class="section">
        1. <span class="bold">CONSIDERATION</span><br>
        IN CONSIDERATION of the Rent sum of <span class="bold">₦{{ number_format($rent->annual_amount, 2) }} ({{ ucwords((new NumberFormatter('en', NumberFormatter::SPELLOUT))->format($rent->annual_amount)) }} Naira Only)</span> per annum paid by the Tenant to the Landlord (the receipt whereof the Landlord hereby acknowledges), the Landlord hereby lets the Demised Premises to the Tenant.
    </div>

    <div class="section">
        2. <span class="bold">THE TENANT COVENANTS WITH THE LANDLORD AS FOLLOWS:</span>
        <ol class="sub-clause">
            <li>To pay the reserved rent in advance on the due date without any deduction or set-off. <span class="bold">Rent is due on the {{ \Carbon\Carbon::create(2000, 1, $rent->due_day)->format('jS') }} day of each month/year.</span></li>
            <li>To pay all utility bills including electricity, water, waste disposal, and service charges consumed on the premises during the tenancy.</li>
            <li>To keep the interior of the Demised Premises, fixtures, and fittings in good and tenantable repair and condition (fair wear and tear excepted).</li>
            <li>Not to make any structural alteration or addition to the Demised Premises without the prior written consent of the Landlord.</li>
            <li>Not to assign, sublet, or part with possession of the Demised Premises or any part thereof without the written consent of the Landlord.</li>
            <li>To use the Demised Premises for RESIDENTIAL PURPOSES ONLY and not for any illegal or immoral purpose.</li>
            <li>To permit the Landlord or his agents at reasonable hours of the day to enter upon and view the condition of the Demised Premises.</li>
            <li>Not to do or permit to be done on the premises anything which may become a nuisance or annoyance to the Landlord or occupiers of adjoining premises.</li>
            <li>To yield up the Demised Premises at the expiration or sooner determination of the tenancy in good and tenantable repair.</li>
        </ol>
    </div>

    <div class="section">
        3. <span class="bold">THE LANDLORD COVENANTS WITH THE TENANT AS FOLLOWS:</span>
        <ol class="sub-clause">
            <li>To pay all rates, ground rents, and taxes payable on the property.</li>
            <li>That the Tenant paying the rent and performing the covenants herein contained shall peaceably hold and enjoy the Demised Premises during the term without any interruption by the Landlord or any person claiming through him.</li>
            <li>To keep the exterior and structural parts of the building in good repair.</li>
        </ol>
    </div>

    <div class="section">
        4. <span class="bold">PROVISO FOR RE-ENTRY</span><br>
        PROVIDED ALWAYS that if the rent reserved or any part thereof shall be in arrears for twenty-one (21) days after becoming due (whether legally demanded or not) or if there be any breach of the Tenant's covenants, it shall be lawful for the Landlord to re-enter the Demised Premises and the tenancy shall determine, but without prejudice to the right of action of the Landlord in respect of any antecedent breach.
    </div>

    <div class="section">
        5. <span class="bold">NOTICE TO QUIT</span><br>
        Either party may terminate this tenancy upon the expiration of the term or by giving <span class="bold">Six (6) Months</span> written notice to the other party.
    </div>

    <div class="section">
        6. <span class="bold">GOVERNING LAW</span><br>
        This Agreement shall be governed by and construed in accordance with the Laws of the Federation of Nigeria and the Tenancy Laws of the State.
    </div>

    <div class="signature-section">
        <div class="section">
            <span class="bold">IN WITNESS WHEREOF</span> the parties have hereunto set their hands and seals the day and year first above written.
        </div>

        <div class="signature-row">
            <div class="signature-box">
                SIGNED by the within named LANDLORD<br><br><br>
                ______________________________<br>
                <span class="bold">OKARO PROPERTY MANAGEMENT</span>
            </div>
            <div class="witness-box">
                IN THE PRESENCE OF:<br><br>
                <span class="bold">Noyb Fundamentals</span><br>
                <span style="font-size: 0.9em; font-style: italic;">(System Creator & Proxy)</span>
            </div>
        </div>

        <div class="signature-row">
            <div class="signature-box">
                SIGNED by the within named TENANT<br><br><br>
                ______________________________<br>
                <span class="bold">{{ $rent->tenant->full_name }}</span>
            </div>
            <div class="witness-box">
                IN THE PRESENCE OF:<br><br>
                <span class="bold">Noyb Fundamentals</span><br>
                <span style="font-size: 0.9em; font-style: italic;">(System Creator & Proxy)</span>
            </div>
        </div>
    </div>

    <div class="container upload-section" data-html2canvas-ignore="true">
        <h3>Agreement Exchange</h3>
        
        <div style="margin-bottom: 20px; padding: 15px; background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 5px;">
            <strong>Step 1:</strong> 
            <button onclick="downloadPDF()" style="background: none; border: none; color: #b45309; text-decoration: underline; cursor: pointer; font-weight: bold; font-size: inherit; padding: 0;">Download Unsigned Copy (PDF)</button>
            <br>
            <span style="font-size: 0.9em; color: #78350f;">(Click above to save the PDF file directly to your device)</span>
        </div>

        <p><strong>Step 2:</strong> Sign the document and upload the scanned copy below. This will replace any existing copy.</p>
        
        @if(session('success'))
            <div style="color: green; margin-bottom: 10px; padding: 10px; background-color: #dcfce7; border-radius: 5px;">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div style="color: red; margin-bottom: 10px; padding: 10px; background-color: #fee2e2; border-radius: 5px;">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($rent->signed_agreement_path)
            <div style="margin-bottom: 20px; padding: 15px; background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 5px;">
                <strong>Current Signed Agreement:</strong> 
                <a href="{{ url('/storage/' . $rent->signed_agreement_path) }}" target="_blank" style="color: #0284c7; font-weight: bold; margin-right: 15px;">View Document</a>
                <a href="{{ route('rents.download-agreement', $rent->id) }}" style="color: #047857; font-weight: bold;">Download Document</a>
            </div>
        @endif

        <form action="{{ route('rents.upload-agreement', $rent) }}" method="POST" enctype="multipart/form-data" style="background: #f9fafb; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
            @csrf
            <div style="margin-bottom: 15px;">
                <label for="signed_agreement" style="display: block; margin-bottom: 5px; font-weight: bold;">Upload Signed Copy (PDF/Image):</label>
                <input type="file" name="signed_agreement" id="signed_agreement" required accept=".pdf,.jpg,.jpeg,.png" style="padding: 8px; width: 100%; box-sizing: border-box;">
            </div>
            <button type="submit" class="print-btn" style="position: static; background-color: #2563eb; width: auto; display: inline-block;">Upload & Overwrite</button>
        </form>
    </div>
</div>

</body>
</html>