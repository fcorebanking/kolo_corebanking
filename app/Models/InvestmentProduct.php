<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentProduct extends Model
{
    use HasFactory;
    public function status()
    {
        return $this->belongsTo(company_status::class, 'status_id', 'id');
    }
    public function frequency()
    {
        return $this->belongsTo(charge_customer_status::class, 'frequency_id', 'id');
    }
    public function company()
    {
        return $this->belongsTo(User::class, 'company_id', 'id');
    }
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by', 'id');
    }

    public function investmentfrequency()
    {
        return $this->belongsTo(frequency::class, 'frequency_id', 'id');
    }

}
