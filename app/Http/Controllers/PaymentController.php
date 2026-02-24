<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Rent;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $query = Payment::with(['rent.tenant', 'rent.unit.building', 'creator'])->latest('payment_date');
        if (auth()->user()->isManager()) {
            $query->whereHas('rent.unit.building', function ($q) {
                $q->where('manager_id', auth()->id());
            });
        }
        $payments = $query->paginate(20);
        return view('payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $rentId = $request->query('rent_id');
        $rentsQuery = Rent::with(['tenant', 'unit.building', 'payments'])->whereHas('tenant', function($q) {
            $q->where('active', true);
        });
        if (auth()->user()->isManager()) {
            $rentsQuery->whereHas('unit.building', function ($q) {
                $q->where('manager_id', auth()->id());
            });
        }
        $rents = $rentsQuery->get();
        
        $selectedRent = $rentId ? Rent::with('payments')->find($rentId) : null;

        return view('payments.create', compact('rents', 'selectedRent'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'rent_id' => 'required|exists:rents,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'status' => 'nullable|in:COMPLETED,PENDING,FAILED',
        ]);

        // Automatically set due_date to match the end of the lease date
        $rent = Rent::find($validated['rent_id']);
        if (auth()->user()->isManager()) {
            if (!$rent || !$rent->unit || !$rent->unit->building || $rent->unit->building->manager_id !== auth()->id()) {
                abort(403, 'Unauthorized action.');
            }
        }
        if ($rent && $rent->end_date) {
            $validated['due_date'] = $rent->end_date;
        }

        Payment::create($validated);

        $rent = Rent::with('unit')->find($validated['rent_id']);
        return redirect()->route('buildings.show', $rent->unit->building_id)->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        $payment->load('rent.tenant');
        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        $rents = Rent::with('tenant')->get();
        return view('payments.edit', compact('payment', 'rents'));
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'rent_id' => 'required|exists:rents,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'status' => 'required|in:COMPLETED,PENDING,FAILED',
        ]);

        $payment->update($validated);

        return redirect()->route('payments.index')->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        if (!auth()->user()->isAdmin()) {
            return back()->with('error', 'Unauthorized action. Only admins can delete payments.');
        }

        $payment->delete();
        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }
}
