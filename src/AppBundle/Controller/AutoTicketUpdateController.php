<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use JMS\Serializer\SerializationContext;
use AppBundle\Entity\Assignee;
use ZipArchive;

/**
 * @Route("/auto_ticket")
 */
class AutoTicketUpdateController extends Controller
{
    /**
     * @Route("/view")
     * @Method({"GET"})
     */
    public function viewAction()
    {
        $em = $this->getDoctrine()->getManager();
        $assigneeRepository = $em->getRepository('AppBundle:Assignee');
        $assigneeList = $assigneeRepository->findBy([], ['id' => 'ASC']);
        $assignee = $this->toArray($assigneeList);

        $em = $this->getDoctrine()->getManager();
        $pool0Repository = $em->getRepository('AppBundle:Pool0');
        $pool0List = $pool0Repository->findBy([], ['id' => 'ASC']);
        $pool0 = $this->toArray($pool0List);

        // error_log('debug = ' . print_r($assignee, true) . "\n", 3,
        // 'C:\Users\Administrator\Desktop\debug.txt');

        return $this->render('AppBundle:AutoTicketUpdate:index.html.twig', [
            'assignee' => $assigneeList,
            'pool0' => $pool0,
        ]);

    }

    /**
     * @Route("/next_assign")
     * @Method({"GET"})
     */
    public function nextAssignAction()
    {
        exec('Test_PoolMonitor.sh', $output);

        foreach ($output as $value) {
            if (strpos($value,'Next assginee            :') !== false) {
                $nextAssginInfo = explode(',', $value);
                $nextAssgin = str_replace(' name = ', '', $nextAssginInfo[1]);
            } elseif (strpos($value,'Next assginee (high_pri) :') !== false) {
                $nextHighAssginInfo = explode(',', $value);
                $nextHighAssgin = str_replace(' name = ', '', $nextHighAssginInfo[1]);
            } elseif (strpos($value,'unassigned tickets') !== false) {
                $unassignNum = str_replace('# of unassigned tickets = ', '', $value);
            } 
        }

        return new JsonResponse([
            'next_assign' => $nextAssgin,
            'next_high_assign' => $nextHighAssgin,
            'unassign_num' => $unassignNum,
        ]);
    }

    /**
     * @Route("/auto_assign")
     * @Method({"PUT"})
     */
    public function autoAssignAction()
    {
        $cmd = 'Test_PoolMonitor.sh -a';
        exec($cmd, $output);

        return new JsonResponse([
            'status' => 'successful',
        ]);
    }

    /**
     * @Route("/manual_assign/{caseId}/{newUserId}")
     * @Method({"POST"})
     */
    public function manualAssignCreateAction($caseId, $newUserId)
    {
        $cmd = 'Test_PoolMonitor.sh -f ' + $caseId + ' ' + $newUserId;
        exec($cmd, $output);
        
        return new JsonResponse([
            'case' => $caseId,
            'new_user' => $newUserId,
        ]);
    }

    /**
     * @Route("/manual_assign/{caseId}/{oldUserId}/{newUserId}")
     * @Method({"PUT"})
     */
    public function manualAssignUpdateAction($caseId, $oldUserId, $newUserId)
    {
        $cmd = 'Test_PoolMonitor.sh ' + $caseId + ' ' + $oldUserId + ' ' + $newUserId;
        exec($cmd, $output);
        
        return new JsonResponse([
            'case' => $caseId,
            'old_user' => $oldUserId,
            'new_user' => $newUserId,
        ]);
    }

    /**
     * @Route("/assignee_status/{userId}/{pto}/{da}")
     * @Method({"PUT"})
     */
    public function ptoChangeAction($userId, $pto, $da)
    {
        // データの追加処理
        // $assignee = new Assignee();
        // $assignee->setName('test');
        // $assignee->setLaps(5);
        // $assignee->setPoint(100);
        // $assignee->setHighPri(2);
        // $assignee->setPto(1);
        // $assignee->setDa(1);

        // $em = $this->getDoctrine()->getManager();
        // $em->persist($assignee);
        // $em->flush();

        // データの更新処理
        // $em = $this->getDoctrine()->getManager();
        // $user = $em->getRepository('AppBundle:Assignee')->find($userId);
        // if (!$user) {
        //     throw $this->createNotFoundException(
        //         'No user found for id '. $userId
        //     );
        // }
        // $user->setPto($pto);
        // $em->flush();

        // $em = $this->getDoctrine()->getManager();
        // $user = $em->getRepository('AppBundle:Assignee')->find($userId);
        // if (!$user) {
        //     throw $this->createNotFoundException(
        //         'No user found for id '. $userId
        //     );
        // }
        // $user->setDa($da);
        // $em->flush();

        $cmd = 'Test_PTO_add.sh ' + $userId + ' ' + $pto + ' ' + $da;
        exec($cmd, $output);
        
        return new JsonResponse([
            'user' => $userId,
            'pto' => $pto,
            'da' => $da,
        ]);
    }

    /**
     * Covert entity object into array.
     *
     * @param $entity
     * @return array
     */
    private function toArray($entity)
    {
        return json_decode($this->serialize($entity), true);
    }

    /**
     * Serialize entity object into json.
     *
     * @param object $entity
     * @return mixed|string
     */
    private function serialize($entity)
    {
        return $this->container->get('jms_serializer')->serialize(
            $entity,
            'json',
            SerializationContext::create()->enableMaxDepthChecks()->setSerializeNull(true)
        );
    }
}

