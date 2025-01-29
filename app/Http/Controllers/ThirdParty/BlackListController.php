<?php

namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BlackListController extends Controller
{
    private $authorization;
    private $baseUrl;

    function __construct()
    {

        if (env('APP_MODE') == 'test') {

            $this->authorization = config('blacklist.authorization_test');
            $this->baseUrl = config('blacklist.base_url_test');
        } else {

            $this->authorization = config('blacklist.authorization_live');
            $this->baseUrl = config('blacklist.base_url_live');
        }

        \logInfo($this->baseUrl, "blacklist Base Url");
    }

    public function crcSearch($bvn)
    {
        $url = "crc-bvn-search/$bvn";

        try {
            $bvnLookup = $this->blacklistGetRequest($url);

            logInfo($bvnLookup, "CRC Search");

            if ($bvnLookup['status'] == "success") {
                if ($bvnLookup['data']['crc'][0]['status'] == "success") {
                    if (isset($bvnLookup["data"]["crc"][0]['data']["MFCREDIT_NANO_SUMMARY"]['SUMMARY']['HAS_CREDITFACILITIES'])) {

                        return [
                            'status' => true,
                            'message' => 'CRC Credit Bureau Search Successfuly',
                            'data' => [
                                "bvn" => $bvn,
                                "type" => "crc_search",
                                "mf_credit_has_credit_facilities" => $bvnLookup["data"]["crc"][0]['data']["MFCREDIT_NANO_SUMMARY"]['SUMMARY']['HAS_CREDITFACILITIES'],
                                "mf_credit_number_of_delinqcredit_facilities" => $bvnLookup["data"]["crc"][0]['data']["MFCREDIT_NANO_SUMMARY"]['SUMMARY']['NO_OF_DELINQCREDITFACILITIES'],
                                "mg_has_credit_facilities" => $bvnLookup["data"]["crc"][0]['data']["MGCREDIT_NANO_SUMMARY"]['SUMMARY']['HAS_CREDITFACILITIES'],
                                "mg_credit_number_of_delinqcredit_facilities" => $bvnLookup["data"]["crc"][0]['data']["MGCREDIT_NANO_SUMMARY"]['SUMMARY']['NO_OF_DELINQCREDITFACILITIES'],
                                "credit_has_credit_facilities" => $bvnLookup["data"]["crc"][0]['data']["CREDIT_NANO_SUMMARY"]['SUMMARY']['HAS_CREDITFACILITIES'],
                                "credit_last_reported_date" => $bvnLookup["data"]["crc"][0]['data']["CREDIT_NANO_SUMMARY"]['SUMMARY']['LAST_REPORTED_DATE'],
                                "credit_number_of_delinqcredit_facilities" => $bvnLookup["data"]["crc"][0]['data']["CREDIT_NANO_SUMMARY"]['SUMMARY']['NO_OF_DELINQCREDITFACILITIES'],
                                "nano_profile_date_of_birth" => $bvnLookup["data"]["crc"][0]['data']["NANO_CONSUMER_PROFILE"]['DATE_OF_BIRTH'],
                                "nano_profile_first_name" => $bvnLookup["data"]["crc"][0]['data']["NANO_CONSUMER_PROFILE"]['FIRST_NAME'],
                                "nano_profile_last_name" => $bvnLookup["data"]["crc"][0]['data']["NANO_CONSUMER_PROFILE"]['LAST_NAME'],
                                "nano_profile_gender" => $bvnLookup["data"]["crc"][0]['data']["NANO_CONSUMER_PROFILE"]['GENDER'],
                                "report_header_email" => $bvnLookup["data"]["crc"][0]['data']["REPORTHEADER"]['MAILTO'],
                                "report_header_product_name" => $bvnLookup["data"]["crc"][0]['data']["REPORTHEADER"]['PRODUCTNAME'],
                                "report_header_report_date" => $bvnLookup["data"]["crc"][0]['data']["REPORTHEADER"]['REPORTDATE']
                            ]
                        ];
                    } else {
                        return ['status' => false, 'message' => $bvnLookup['data']['crc'][0]['message']];
                    }
                } else {
                    return ['status' => false, 'message' => $bvnLookup['data']['crc'][0]['message']];
                }
            } else {
                return ['status' => false, 'message' => 'Failed to Complete CRC Search'];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lendsqr Error");

            return ['status' => false, 'message' => 'Failed to Complete CRC Search'];
        }
    }

    public function firstCentral($bvn)
    {
        $url = "firstcentral-basic-search/";

        $body = [
            'bvn' => $bvn
        ];

        try {
            $bvnLookup = $this->blacklistPostRequest($url, $body);

            logInfo($bvnLookup, "First Central Search");

            if ($bvnLookup['status'] == "success") {
                if ($bvnLookup['data']['firstcentral'][0]['status'] == "success") {
                    if (isset($bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["Header"])) {

                        return [
                            'status' => true,
                            'message' => 'First Central Search Successfuly',
                            'data' => [
                                "bvn" => $bvn,
                                "type" => "first_central",

                                "personal_details_summary" => [
                                    "header" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["Header"],
                                    "consumer_id" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["ConsumerID"],
                                    "reference_no" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["ReferenceNo"],
                                    "nationality" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["Nationality"],
                                    "national_id_no" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["NationalIDNo"],
                                    "passport_no" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["PassportNo"],
                                    "driversLicense_no" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["DriversLicenseNo"],
                                    "bank_verification_no" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["BankVerificationNo"],
                                    "pencom_id_no" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["PencomIDNo"],
                                    "otherid_no" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["otheridNo"],
                                    "birth_date" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["BirthDate"],
                                    "dependants" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["Dependants"],
                                    "first_central_gender" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["Gender"],
                                    "marital_status" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["MaritalStatus"],
                                    "residential_address1" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["ResidentialAddress1"],
                                    "residential_address2" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["ResidentialAddress2"],
                                    "residential_address3" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["ResidentialAddress3"],
                                    "residential_address4" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["ResidentialAddress4"],
                                    "postal_address1" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["PostalAddress1"],
                                    "postal_address2" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["PostalAddress2"],
                                    "postal_address3" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["PostalAddress3"],
                                    "postal_address4" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["PostalAddress4"],
                                    "home_telephone_no" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["HomeTelephoneNo"],
                                    "Work_telephone_no" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["WorkTelephoneNo"],
                                    "cellular_no" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["CellularNo"],
                                    "email_address" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["EmailAddress"],
                                    "employer_detail" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["EmployerDetail"],
                                    "property_owned_type" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["PropertyOwnedType"],
                                    "surname" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["Surname"],
                                    "first_name" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["FirstName"],
                                    "other_names" => $bvnLookup['data']['firstcentral'][0]['data']["PersonalDetailsSummary"]["OtherNames"],
                                ],

                                "credit_account_summary" => [
                                    "total_monthly_installment" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalMonthlyInstalment"],
                                    "total_outstanding_debt" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalOutstandingdebt"],
                                    "total_account_arrear" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalAccountarrear"],
                                    "amount_arrear" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["Amountarrear"],
                                    "total_accounts" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalAccounts"],
                                    "total_accounts1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalAccounts1"],
                                    "total_accountin_god_condition" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalaccountinGodcondition"],
                                    "total_accountin_good_condition" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalaccountinGoodcondition"],
                                    "total_numberof_judgement" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalNumberofJudgement"],
                                    "total_judgement_amount" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalJudgementAmount"],
                                    "last_judgement_date" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["LastJudgementDate"],
                                    "total_numberof_dishonoured" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalNumberofDishonoured"],
                                    "total_dishonoured_amount" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalDishonouredAmount"],
                                    "last_bounced_cheques_date" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["LastBouncedChequesDate"],
                                    "total_monthly_instalment1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalMonthlyInstalment1"],
                                    "total_outstandingdebt1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalOutstandingdebt1"],
                                    "total_account_arrear1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalAccountarrear1"],
                                    "amount_arrear1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["Amountarrear1"],
                                    "total_accountin_god_condition1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalaccountinGodcondition1"],
                                    "total_accountin_bad_condition" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalaccountinBadcondition"],
                                    "total_number_of_judgement1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalNumberofJudgement1"],
                                    "total_judgement_amount1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalJudgementAmount1"],
                                    "last_judgement_date1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["LastJudgementDate1"],
                                    "total_numberof_dishonoured1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalNumberofDishonoured1"],
                                    "total_dishonoured_amount1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["TotalDishonouredAmount1"],
                                    "last_bounced_cheques_date1" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["LastBouncedChequesDate1"],
                                    "rating" => $bvnLookup['data']['firstcentral'][0]['data']["CreditAccountSummary"]["Rating"]
                                ],
                                "account_rating" => [
                                    "no_of_home_loan_accounts_good" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfHomeLoanAccountsGood"],
                                    "no_of_home_loan_accounts_bad" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfHomeLoanAccountsBad"],
                                    "no_of_auto_loan_acounts_good" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfAutoLoanccountsGood"],
                                    "no_of_auto_loan_accounts_bad" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfAutoLoanAccountsBad"],
                                    "no_of_studyLoanAccounts_good" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfStudyLoanAccountsGood"],
                                    "no_of_studyLoanAccounts_bad" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfStudyLoanAccountsBad"],
                                    "no_of_personal_loan_accounts_good" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfPersonalLoanAccountsGood"],
                                    "no_of_personal_loan_accounts_bad" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfPersonalLoanAccountsBad"],
                                    "no_of_credit_card_accounts_good" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfCreditCardAccountsGood"],
                                    "no_of_credit_card_accounts_bad" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfCreditCardAccountsBad"],
                                    "no_of_retail_accounts_good" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfRetailAccountsGood"],
                                    "no_of_retail_accounts_bad" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfRetailAccountsBad"],
                                    "no_of_joint_loan_ccounts_good" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfJointLoanAccountsGood"],
                                    "no_of_joint_loan_accounts_bad" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfJointLoanAccountsBad"],
                                    "no_of_telecom_ccounts_good" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfTelecomAccountsGood"],
                                    "no_of_telecom_accounts_bad" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfTelecomAccountsBad"],
                                    "no_of_other_ccounts_good" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfOtherAccountsGood"],
                                    "no_of_other_accounts_bad" => $bvnLookup['data']['firstcentral'][0]['data']['AccountRating']["NoOfOtherAccountsBad"],
                                ],
                                "subject_list" => [
                                    "consumer_id" => $bvnLookup['data']['firstcentral'][0]['data']['SubjectList']["ConsumerID"],
                                    "search_output" => $bvnLookup['data']['firstcentral'][0]['data']['SubjectList']["SearchOutput"],
                                    "reference" => $bvnLookup['data']['firstcentral'][0]['data']['SubjectList']["Reference"],
                                ],
                                "enquiry_details" => [
                                    "subscriber_enquiry_result_id" => $bvnLookup['data']['firstcentral'][0]['data']["EnquiryDetails"]["SubscriberEnquiryResultID"],
                                    "product_id" => $bvnLookup['data']['firstcentral'][0]['data']["EnquiryDetails"]["ProductID"],
                                    "matching_rate" => $bvnLookup['data']['firstcentral'][0]['data']["EnquiryDetails"]["MatchingRate"],
                                    "subscriber_enquiry_engineID" => $bvnLookup['data']['firstcentral'][0]['data']["EnquiryDetails"]["SubscriberEnquiryEngineID"],
                                ]
                            ]
                        ];
                    } else {
                        return ['status' => false, 'message' => $bvnLookup['data']['firstcentral'][0]['data']["Error"]];
                    }
                } else {
                    return ['status' => false, 'message' => $bvnLookup['data']['firstcentral'][0]['data']["Error"]];
                }
            } else {
                return ['status' => false, 'message' => $bvnLookup['message']];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Blacklist Error");

            return ['status' => false, 'message' => 'Failed to First Central Search'];
        }
    }


    public function blacklistPostRequest($endpoint, $body)
    {
        \logInfo("$this->baseUrl$endpoint");
        \logInfo($body);
        $response = Http::withHeaders($this->blacklistHeader())->post("$this->baseUrl$endpoint", $body);
        return $response->json();
    }

    public function blacklistGetRequest($endpoint)
    {
        logInfo("$this->baseUrl$endpoint", "Convertion url");
        logInfo($this->blacklistHeader(), "Authorization key");
        $response = Http::withHeaders($this->blacklistHeader())->get("$this->baseUrl$endpoint");
        return $response->json();
    }

    public function blacklistHeader()
    {
        $header = [
            "Content-Type" => "application/json",
            "Authorization" => "$this->authorization",
        ];

        return $header;
    }
}
