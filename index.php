<?php
/**
 * Short description for file
 *
 * Long description for file (if any)...
 *
 * PHP version 7.4
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @author     Nguyen Van Tam <tamnv90@gmail.com>
 * @function   Travel cost of a particular company is the total travel price
 * of employees in that company and its child companies
 * @copyright  2022
 */

const API_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/';

class Travel
{
    // get list travel from api
    public function get_list_travels(){
        $api = new API();
        $get_data = $api->callAPI('GET', API_URL.'travels', false);
        return json_decode($get_data);
    }

    public function map_costs($travels): array
    {
        $travels_cost = array();
        foreach ($travels as $travel){
            $travels_cost[$travel->companyId] += $travel->price;
        }
        return $travels_cost;
    }
}

class Company
{
    // get list companies from api
    public function get_list_companies(){
        $api = new API();
        $get_data = $api->callAPI('GET', API_URL.'companies', false);
        return json_decode($get_data, true);
    }
}
//Class API
class API
{
    public function callAPI($method, $url, $data){
        $curl = curl_init();
        switch ($method){
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);
                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // EXECUTE:
        $result = curl_exec($curl);
        if(!$result){die("Connection Failure");}
        curl_close($curl);
        return $result;
    }
}


class TestScript
{
    public function execute()
    {
        $start = microtime(true);

        //get costs and mapping data by company id
        $objTravel = new Travel();
        $costData = $objTravel->get_list_travels();
        $cost = $objTravel->map_costs($costData);

        // get list companies and build tree
        $clsCompany = new Company();
        $companies = $clsCompany->get_list_companies();
        $tree = $this->build_tree($companies,$cost);

        echo json_encode($tree);
        echo '<br/>';
        echo 'Total time: '.  (microtime(true) - $start);
        echo '<pre>';
        var_dump($tree);
        echo '</pre>';
        die();
    }

    //Build tree from flat array
    function build_tree(array &$elements,$costs, $parentId = '0') {
        $branch = array();
        foreach ($elements as &$element) {
            if ($element['parentId'] == $parentId) {
                $children = $this->build_tree($elements,$costs, $element['id']);
                $element['currentCost'] = @$costs[$element['id']];
                if ($children) {
                    $element['childrenCost'] = $this->get_children_sum($children,$costs);
                    $element['totalCost'] = $element['childrenCost'] + $element['currentCost'];
                    $element['children'] = $children;
                }
                $branch[$element['id']] = $element;
                unset($element);
            }
        }
        return $branch;
    }

    function get_children_sum($array,$costs)
    {
        $sum = 0;
        if (count($array)>0)
        {
            foreach ($array as $item)
            {
                $sum += $costs[$item['id']];
                $sum += $this->get_children_sum($item['children'],$costs);
            }
            return $sum;
        }
        else return 0;
    }

}

(new TestScript())->execute();