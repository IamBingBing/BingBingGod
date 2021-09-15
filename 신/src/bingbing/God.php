<?php
namespace bingbing;

use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\scheduler\PluginTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\level\Position;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\entity\Villager;
use pocketmine\entity\NPC;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\level\Level;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\TextContainer;
use pocketmine\entity\Effect;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\EffectInstance;

class God extends PluginBase implements Listener
{

    private $m = "§b[§f 영주 §b]§F";

    private $war = "false";

    private $time = [];

    public function onEnable()
    {
        $this->getServer()
            ->getPluginManager()
            ->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->playerdb = new Config($this->getDataFolder() . "player.yml", Config::YAML);
        $this->p = $this->playerdb->getAll();
        $this->uniondb = new Config($this->getDataFolder() . "union.yml", Config::YAML);
        $this->union = $this->uniondb->getAll();
        $this->npcdb = new Config($this->getDataFolder() . "npc.yml", Config::YAML);
        $this->npc = $this->npcdb->getAll();
        $this->getServer()
            ->getScheduler()
            ->scheduleRepeatingTask(new money($this), 180 * 20);
        $this->getServer()
            ->getScheduler()
            ->scheduleRepeatingTask(new task1($this), 1 * 30);
        $this->grounddb = new Config($this->getDataFolder() . "ground.yml", Config::YAML, []);
        $this->ground = $this->grounddb->getAll();
        $this->list = new Config($this->getDataFolder() . "unions.yml", Config::YAML, [
            "union" => []
        ]);
        $this->l = $this->list->getAll();
        $this->setting = new Config($this->getDataFolder() . "setting.yml", Config::YAML, [
            "warland" => "1234567890"
        ]);
        $this->set = $this->setting->getAll();
    }

    public function onLoad()
    {
        Entity::registerEntity(Villager::class, true);
    }

    public function join(PlayerJoinEvent $event)
    {
        $name = $event->getPlayer()->getName();
        if (! isset($this->p[$name])) {
            $this->p[$name] = [];
            $this->p[$name]["sinbon"] = "영주";
            $this->p[$name]["quest"] = "없습니다";
            $this->p[$name]["qm"] = "0";
            $this->p[$name]["ql"] = "0";
            $this->p[$name]["level"] = 0;
            $this->p[$name]["union"] = "무소속";
            $this->p[$name]["list"] = [];
            $this->p[$name]["npcn"] = 0;
            $this->npc[$name] = [];
            $this->npc[$name]["number"] = 0;
        }
        if (! isset($this->npc[$name]["NPCnumber"])) {
            $this->npc[$name]["NPCnumber"] = $this->npc[$name]["number"];
        }
        $this->time[$name] = 0;
        $event->getPlayer()->addEffect(new EffectInstance(Effect::getEffect(16), 100000000, 3));
        $this->save();
    }

    public function save()
    {
        $this->playerdb->setAll($this->p);
        $this->playerdb->save();

        $this->npcdb->setAll($this->npc);
        $this->npcdb->save();

        $this->uniondb->setAll($this->union);
        $this->uniondb->save();

        $this->grounddb->setAll($this->ground);
        $this->grounddb->save();

        $this->list->setAll($this->l);
        $this->list->save();
    }

    public function place(BlockPlaceEvent $event)
    {
        $block = $event->getBlock();
        $x = $block->getFloorX();
        $y = $block->getFloorY();
        $z = $block->getFloorZ();
        $player = $event->getPlayer();
        $name = $event->getPlayer()->getName();
        if ($event->getBlock()->getId() == "41" and $event->getPlayer()
            ->getLevel()
            ->getFolderName() == "1" and $player->isOp()) { // gold block
            $this->ground[$x . ":" . $y . ":" . $z] = [];
            $this->ground[$x . ":" . $y . ":" . $z]["own"] = "UNKNOWN";
            $this->ground[$x . ":" . $y . ":" . $z]["health"] = mt_rand(1000, 3000);
        }
    }

    public function touch(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $touch = $event->getBlock();
        $name = $event->getPlayer()->getName();
        if ($event->getPlayer()
            ->getLevel()
            ->getFolderName() == "hi") {
            if ($event->getItem()->getId() == "369" and $event->getBlock()
                ->getLevel()
                ->getBlock(new Position($touch->getFloorX(), $touch->getFloorY(), $touch->getFloorZ()))
                ->getId() == "214" and $this->npc[$name]["number"] <= 10) {
                $job = $this->job();

                $nbt = Entity::createBaseNBT(new Vector3($touch->getFloorX() + 0.5, $touch->y + 1, $touch->getFloorZ() + 0.5), new Vector3($player->getMotion()->x, $player->getMotion()->y, $player->getMotion()->z), $player->yaw, $player->pitch);
                $entity = Entity::createEntity(15, $player->getLevel(), $nbt);

                switch ($job) {
                    case 1:
                        $entity->setNameTag("일반 백성 ");
                        break;
                    case 2:
                        $entity->setNameTag("전문가 백성");
                        break;
                    case 3:
                        $entity->setNameTag("재벌 백성");
                        break;
                }
                $entity->spawnToAll();

                $this->npc[$name][$this->npc[$name]["NPCnumber"]] = [];
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["x"] = $touch->getFloorX() + 0.5;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["y"] = $touch->y + 1;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["z"] = $touch->getFloorZ() + 0.5;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["level"] = 1;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["job"] = $job;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["money"] = 0;
                $this->npc[$name]["number"] = $this->npc[$name]["number"] + 1;
                $this->npc[$name]["NPCnumber"] = $this->npc[$name]["NPCnumber"] + 1;

                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY(), $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX() + 1, $touch->getFloorY() + 1, $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX() - 1, $touch->getFloorY() + 1, $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 1, $touch->getFloorZ() + 1), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 1, $touch->getFloorZ() - 1), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 3, $touch->getFloorZ()), Block::get(7));

                $event->getPlayer()
                    ->getInventory()
                    ->removeItem(Item::get(369, 0, 1));
                $event->getPlayer()->sendMessage($this->m . "npc가 정상적으로 생성되었습니다. 현제 npc는 " . $this->npc[$name]["number"] . "명 입니다");
            }
            if ($event->getItem()->getId() == "369" and $event->getBlock()
                ->getLevel()
                ->getBlock(new Position($touch->getFloorX(), $touch->getFloorY(), $touch->getFloorZ()))
                ->getId() == "133" and $this->npc[$name]["number"] <= 10) {

                $nbt = Entity::createBaseNBT(new Vector3($touch->getFloorX() + 0.5, $touch->y + 1, $touch->getFloorZ() + 0.5), new Vector3($player->getMotion()->x, $player->getMotion()->y, $player->getMotion()->z), $player->yaw, $player->pitch);
                $entity = Entity::createEntity(15, $player->getLevel(), $nbt);
                $entity->setNameTag("전문가 백성");
                $entity->spawnToAll();

                $this->npc[$name][$this->npc[$name]["NPCnumber"]] = [];
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["x"] = $touch->getFloorX() + 0.5;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["y"] = $touch->y + 1;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["z"] = $touch->getFloorZ() + 0.5;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["level"] = 1;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["job"] = 2;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["money"] = 0;
                $this->npc[$name]["number"] = $this->npc[$name]["number"] + 1;
                $this->npc[$name]["NPCnumber"] = $this->npc[$name]["NPCnumber"] + 1;

                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY(), $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX() + 1, $touch->getFloorY() + 1, $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX() - 1, $touch->getFloorY() + 1, $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 1, $touch->getFloorZ() + 1), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 1, $touch->getFloorZ() - 1), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 3, $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()->sendMessage($this->m . "npc가 정상적으로 생성되었습니다. 현제 npc는 " . $this->npc[$name]["number"] . "명 입니다");
            }
            if ($event->getItem()->getId() == "369" and $event->getBlock()
                ->getLevel()
                ->getBlock(new Position($touch->getFloorX(), $touch->getFloorY(), $touch->getFloorZ()))
                ->getId() == "22" and $this->npc[$name]["number"] <= 10) {

                $nbt = Entity::createBaseNBT(new Vector3($touch->getFloorX() + 0.5, $touch->y + 1, $touch->getFloorZ() + 0.5), new Vector3($player->getMotion()->x, $player->getMotion()->y, $player->getMotion()->z), $player->yaw, $player->pitch);
                $entity = Entity::createEntity(15, $player->getLevel(), $nbt);
                $entity->spawnToAll();
                $entity->setNameTag("재벌 백성");

                $this->npc[$name][$this->npc[$name]["NPCnumber"]] = [];
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["x"] = $touch->getFloorX() + 0.5;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["y"] = $touch->y + 1;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["z"] = $touch->getFloorZ() + 0.5;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["level"] = 1;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["job"] = 3;
                $this->npc[$name][$this->npc[$name]["NPCnumber"]]["money"] = 0;
                $this->npc[$name]["number"] = $this->npc[$name]["number"] + 1;
                $this->npc[$name]["NPCnumber"] = $this->npc[$name]["NPCnumber"] + 1;

                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY(), $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX() + 1, $touch->getFloorY() + 1, $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX() - 1, $touch->getFloorY() + 1, $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 1, $touch->getFloorZ() + 1), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 1, $touch->getFloorZ() - 1), Block::get(7));
                $event->getPlayer()
                    ->getLevel()
                    ->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 3, $touch->getFloorZ()), Block::get(7));
                $event->getPlayer()->sendMessage($this->m . "npc가 정상적으로 생성되었습니다. 현제 npc는 " . $this->npc[$name]["number"] . "명 입니다");
            }
            /*
             * if ($event->getItem()->getId() == "384" && $event->getItem()->getCustomName() !== "유리병"){
             * $cd =$event->getItem()->getCustomName();
             * $c = explode(":", $cd);
             * if (isset($c[0]) && isset($c[1]) && isset($c[2]) ){
             * $nbt = Entity::createBaseNBT(new Vector3($touch->getFloorX()+0.5 , $touch->y+1 , $touch->getFloorZ()+0.5) , new Vector3($player->getMotion()->x , $player->getMotion()->y , $player->getMotion()->z ) , $player->yaw , $player->pitch );
             * $entity = Entity::createEntity(15, $player->getLevel(),$nbt);
             * $entity->spawnToAll();
             * switch ($c[2]){
             * case 1:
             * $entity->setNameTag( "일반 백성 ");
             *
             * break;
             * case 2:
             * $entity->setNameTag( "전문가 백성");
             *
             * break;
             * case 3:
             * $entity->setNameTag( "재벌 백성");
             *
             * break;
             *
             * }
             * $this->npc[$name][$this->npc[$name]["number"]] = [];
             * $this->npc[$name][$this->npc[$name]["number"]]["x"] = $touch->getFloorX()+0.5;
             * $this->npc[$name][$this->npc[$name]["number"]]["y"] = $touch->y+1;
             * $this->npc[$name][$this->npc[$name]["number"]]["z"] = $touch->getFloorZ()+0.5;
             * $this->npc[$name][$this->npc[$name]["number"]]["level"] = $c[1];
             * $this->npc[$name][$this->npc[$name]["number"]]["job"] = $c[2];
             * $this->npc[$name][$this->npc[$name]["number"]]["money"] = $c[0];
             * $this->npc[$name]["number"]= $this->npc[$name]["number"]+1;
             * $this->npc[$name]["NPCnumber"]= $this->npc[$name]["NPCnumber"]+1;
             *
             * $event->getPlayer()->getLevel()->setBlock(new Position($touch->getFloorX(),$touch->getFloorY(),$touch->getFloorZ()), Block::get(7));
             * $event->getPlayer()->getLevel()->setBlock(new Position($touch->getFloorX()+1,$touch->getFloorY()+1,$touch->getFloorZ()), Block::get(7) );
             * $event->getPlayer()->getLevel()->setBlock(new Position($touch->getFloorX()-1,$touch->getFloorY()+1,$touch->getFloorZ()),Block::get(7) );
             * $event->getPlayer()->getLevel()->setBlock(new Position($touch->getFloorX(),$touch->getFloorY()+1,$touch->getFloorZ()+1),Block::get(7) );
             * $event->getPlayer()->getLevel()->setBlock(new Position($touch->getFloorX(),$touch->getFloorY()+1,$touch->getFloorZ()-1),Block::get(7) );
             * $event->getPlayer()->getLevel()->setBlock(new Position($touch->getFloorX(),$touch->getFloorY()+3,$touch->getFloorZ()), Block::get(7) );
             * $event->getPlayer()->sendMessage($this->m."npc가 정상적으로 생성되었습니다. 현제 npc는 ".$this->npc[$name]["number"]."명 입니다");
             * }
             * }
             */
        } else {
            if ($touch->getId() == "41") {
                if (isset($this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()])) {
                    $this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["health"] = $this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["health"] - mt_rand(5, 10);
                    $player->sendMessage($this->m . $this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["own"] . "땅의 남은 체력은 " . $this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["health"] . " 입니다");
                    if ($this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["health"] < 0) {

                        if ($this->union[$this->p[$name]["union"]]["landcount"] < $this->union[$this->p[$name]["union"]]["level"] + 2) {
                            $this->union[$this->p[$name]["union"]]["landcount"] = $this->union[$this->p[$name]["union"]]["landcount"] + 1;
                            $player->sendMessage($this->m . $this->p[$name]["union"] . "연합원 " . $player->getName() . "님이 " . $this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["own"] . "소유의 땅을 점령에 성공하셨습니다");

                            if ($this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["own"] != "UNKNOWN") {
                                $this->union[$this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["own"]]["landcount"] = $this->union[$this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["own"]]["landcount"] - 1;
                            }
                            $this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["own"] = $this->p[$name]["union"];
                        } else {
                            $player->sendMessage($this->m . $this->p[$name]["union"] . "연합원 " . $player->getName() . "님이 " . $this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["own"] . "소유의 땅을 중립지역으로 바꾸었습니다");
                            $this->ground[$touch->getFloorX() . ":" . $touch->getFloorY() . ":" . $touch->getFloorZ()]["own"] = "UNKNOWN";
                        }
                    }
                }
            }
        }
    }

    public function rand(int $d)
    {
        $r = mt_rand(1, 100);
        if ($r < $d) {
            return "true";
        }
    }

    public function break(BlockBreakEvent $event)
    {
        $b = $event->getBlock();
        $p = $event->getPlayer();
        $name = $p->getName();
        $m = "§b[§f 영주 §b]§f ";
        $x = $b->getFloorX();
        $y = $b->getFloorY();
        $z = $b->getFloorZ();

        if ($b->getId() == "142" and $this->p[$p->getName()]["quest"] == "감자") {
            $this->p[$name]["ql"] = $this->p[$name]["ql"] + 1;
            if ($this->p[$name]["ql"] >= $this->p[$name]["qm"]) {
                $this->p[$name]["quest"] = "없습니다";
                if ($this->rand(40) == "true") {
                    $this->npc[$name][$this->p[$name]["npcn"]]["level"] = $this->npc[$name][$this->p[$name]["npcn"]]["level"] + 1;
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 1 올랐습니다.");
                } else {
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 오르지 못하였습니다.");
                }
            }
            $p->sendMessage($m . $this->p[$p->getName()]["quest"] . " 를 캔 횟수" . $this->p[$p->getName()]["ql"] . " / " . $this->p[$p->getName()]["qm"] . "입니다.");
        } else if ($b->getId() == "141" and $this->p[$p->getName()]["quest"] == "당근") {
            $this->p[$name]["ql"] = $this->p[$name]["ql"] + 1;
            if ($this->p[$name]["ql"] >= $this->p[$name]["qm"]) {
                $this->p[$name]["quest"] = "없습니다";
                if ($this->rand(40) == "true") {
                    $this->npc[$name][$this->p[$name]["npcn"]]["level"] = $this->npc[$name][$this->p[$name]["npcn"]]["level"] + 1;
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 1 올랐습니다.");
                } else {
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 오르지 못하였습니다.");
                }
            }
            $p->sendMessage($m . $this->p[$p->getName()]["quest"] . " 당근을 캔 횟수" . $this->p[$p->getName()]["ql"] . " / " . $this->p[$p->getName()]["qm"] . "입니다.");
        } else if ($b->getId() == "14" and $this->p[$p->getName()]["quest"] == "돈부족") {
            $this->p[$name]["ql"] = $this->p[$name]["ql"] + 1;
            if ($this->p[$name]["ql"] >= $this->p[$name]["qm"]) {
                $this->p[$name]["quest"] = "없습니다";
                if ($this->rand(40) == "true") {
                    $this->npc[$name][$this->p[$name]["npcn"]]["level"] = $this->npc[$name][$this->p[$name]["npcn"]]["level"] + 1;
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 1 올랐습니다.");
                } else {
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 오르지 못하였습니다.");
                }
            }
            $p->sendMessage($m . $this->p[$p->getName()]["quest"] . " 캐는 횟수" . $this->p[$p->getName()]["ql"] . " / " . $this->p[$p->getName()]["qm"] . "입니다.");
        } else if ($b->getId() == "1" and $this->p[$p->getName()]["quest"] == "돌조각상") {
            $this->p[$name]["ql"] = $this->p[$name]["ql"] + 1;
            if ($this->p[$name]["ql"] >= $this->p[$name]["qm"]) {
                $this->p[$name]["quest"] = "없습니다";
                if ($this->rand(40) == "true") {
                    $this->npc[$name][$this->p[$name]["npcn"]]["level"] = $this->npc[$name][$this->p[$name]["npcn"]]["level"] + 1;
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 1 올랐습니다.");
                } else {
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 오르지 못하였습니다.");
                }
            }
            $p->sendMessage($m . $this->p[$p->getName()]["quest"] . " 캐는 횟수" . $this->p[$p->getName()]["ql"] . " / " . $this->p[$p->getName()]["qm"] . "입니다.");
        } 
        else if ($b->getId() == "129" and $this->p[$p->getName()]["quest"] == "애매랄드") {
            $this->p[$name]["ql"] = $this->p[$name]["ql"] + 1;
            if ($this->p[$name]["ql"] >= $this->p[$name]["qm"]) {
                $this->p[$name]["quest"] = "없습니다";
                if ($this->rand(40) == "true") {
                    $this->npc[$name][$this->p[$name]["npcn"]]["level"] = $this->npc[$name][$this->p[$name]["npcn"]]["level"] + 1;
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 1 올랐습니다.");
                } else {
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 오르지 못하였습니다.");
                }
            }
            $p->sendMessage($m . $this->p[$p->getName()]["quest"] . " 캐는 횟수" . $this->p[$p->getName()]["ql"] . " / " . $this->p[$p->getName()]["qm"] . "입니다.");
        } else if ($b->getId() == "56" and $this->p[$p->getName()]["quest"] == "다이아") {
            $this->p[$name]["ql"] = $this->p[$name]["ql"] + 1;
            if ($this->p[$name]["ql"] >= $this->p[$name]["qm"]) {
                $this->p[$name]["quest"] = "없습니다";
                if ($this->rand(40) == "true") {
                    $this->npc[$name][$this->p[$name]["npcn"]]["level"] = $this->npc[$name][$this->p[$name]["npcn"]]["level"] + 1;
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 1 올랐습니다.");
                } else {
                    $p->sendMessage($this->m . "당신의 " . $this->p[$name]["npcn"] . "번 npc의 레벨이 오르지 못하였습니다.");
                }
            }
            $p->sendMessage($m . $this->p[$p->getName()]["quest"] . " 캐는 횟수" . $this->p[$p->getName()]["ql"] . " / " . $this->p[$p->getName()]["qm"] . "입니다.");
        } else if ($b->getId() == "41") {
            if (isset($this->ground[$x . ":" . $y . ":" . $z])) {
                if ($p->isOp()) {
                    if ($this->ground[$x . ":" . $y . ":" . $z]["own"] != "UNKNOWN") {

                        -- $this->union[$this->ground[$x . ":" . $y . ":" . $z]["own"]]["landcount"];
                    }
                    unset($this->ground[$x . ":" . $y . ":" . $z]);
                    $p->sendMessage("삭제함");
                } else {
                    $event->setCancelled(true);
                }
            }
        }
    }

    /*
     * public function blink(PlayerBucketFillEvent$event){
     * if ($event->getItem()->getId() == 374){
     * $this->p[$name]["ql"] = $this->p[$name]["ql"]+ 1 ;
     * if ($this->p[$name]["ql"] == $this->p[$name]["qm"]){
     * $this->p[$name]["quest"] == "없습니다";
     * $event->getPlayer()->getInventory()->removeItem(Item::get(373,0,1));
     * if ($this->rand(40) == "true"){
     * $this->npc[$name][$this->p[$name]["npcn"]]["level"] =$this->npc[$name][$this->p[$name]["npcn"]]["level"] +1;
     * $p->sendMessage($this->m ."당신의 ".$this->p[$name]["npcn"]."번 npc의 레벨이 1 올랐습니다.");
     * }
     * }
     *
     * }
     * }
     */
    public function damage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        $touch = $entity;

        $m = "§b[§f 영주 §b]§f ";

        if ($event instanceof EntityDamageByEntityEvent or $event instanceof EntityDamageByBlockEvent or $event instanceof EntityDamageByChildEntityEvent) {
            $damager = $event->getDamager();
            $player = $damager;
            $event->setDamage(0);
            if ($damager instanceof Player and $entity instanceof Villager) {
                $name = $damager->getName();
                for ($a = 0; $a < $this->npc[$name]["NPCnumber"]; $a ++) {
                    if ($entity->getX() == $this->npc[$name][$a]["x"] and $entity->getY() == $this->npc[$name][$a]["y"] and $entity->getZ() == $this->npc[$name][$a]["z"]) {
                        if ($damager->getInventory()
                            ->getItemInHand()
                            ->getId() != 399 && $damager->getInventory()
                            ->getItemInHand()
                            ->getId() != 406) {

                            if ($this->npc[$name][$a]["money"] > 1000) {
                                $damager->sendMessage($m . "네네 영주님 세금을 내겠습니다요.");
                                $damager->getInventory()->addItem(Item::get(388, 0, floor($this->npc[$name][$a]["money"] / 1000)));
                                $damager->sendMessage($m . "에매랄드 " . floor($this->npc[$name][$a]["money"] / 1000) . "개를 세금으로 받았습니다.");
                                $this->npc[$name][$a]["money"] = $this->npc[$name][$a]["money"] % 1000;
                                break;
                            } else {
                                $damager->sendMessage($m . "네네 영주님 조금만 더 시간을 주십쇼!");
                                break;
                            }
                        } /*
                         * else if($damager->getInventory()->getItemInHand()->getId() == "374"){
                         * $damager->getInventory()->getItemInHand()->setCustomName($this->npc[$name][$a]["money"].":".$this->npc[$name][$a]["level"].":".$this->npc[$name][$a]["job"].":주민");
                         * $entity->kill();
                         * $this->npc[$name]["number"] = $this->npc[$name]["number"]-1;
                         * unset($this->npc[$name][$a]);
                         * break;
                         *
                         * }
                         */
                        else if ($damager->getInventory()
                            ->getItemInHand()
                            ->getId() == "406") {
                            $entity->kill();
                            $damager->getInventory()->removeItem(Item::get(406, 0, 1));
                            $this->npc[$name]["number"] = $this->npc[$name]["number"] - 1;
                            $this->npc[$name][$a]["job"] = 0;
                            $damager->getLevel()->setBlock(new Position($touch->getFloorX(), $touch->getFloorY(), $touch->getFloorZ()), Block::get(0));
                            $damager->getLevel()->setBlock(new Position($touch->getFloorX() + 1, $touch->getFloorY() + 1, $touch->getFloorZ()), Block::get(0));
                            $damager->getLevel()->setBlock(new Position($touch->getFloorX() - 1, $touch->getFloorY() + 1, $touch->getFloorZ()), Block::get(0));
                            $damager->getLevel()->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 1, $touch->getFloorZ() + 1), Block::get(0));
                            $damager->getLevel()->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 1, $touch->getFloorZ() - 1), Block::get(0));
                            $damager->getLevel()->setBlock(new Position($touch->getFloorX(), $touch->getFloorY() + 3, $touch->getFloorZ()), Block::get(0));
                            $damager->sendMessage($this->m . "삭제 완료");
                            break;
                        } else {
                            $q = $this->quest();
                            $this->p[$damager->getName()]["quest"] = $q;
                            $this->p[$damager->getName()]["npcn"] = $a;
                            $damager->getInventory()->removeItem(Item::get(399, 0, 1));
                            switch ($q) {
                                case "감자":
                                    $damager->sendMessage($m . "저희 문제는 식량부족입니다. 감자  100개만 캐주세요 부탁드립니다");
                                    $this->p[$damager->getName()]["qm"] = 100;

                                    break;
                                case "당근":
                                    $damager->sendMessage($m . "저희 문제는 식량부족입니다. 당근  100개만 캐주세요 부탁드립니다");
                                    $this->p[$damager->getName()]["qm"] = 100;

                                    break;

                                case "돌조각상":
                                    $damager->sendMessage($m . "저희는 집을 지을 돌이 부족합니다. 광산에서 돌 200개를 캐주세요");
                                    $this->p[$damager->getName()]["qm"] = 200;

                                    break;
                                case "돈부족":
                                    $damager->sendMessage($m . "저희는 지금 빚이 좀 있습니다. 빚을 갚게 광산에서 금을 80개만 캐주세요");
                                    $this->p[$damager->getName()]["qm"] = 80;

                                    break;

                                case "에매랄드":
                                    $damager->sendMessage($m . "저희 사치 좀 부리게 광산에서 에매랄드 30개 캐주세요");
                                    $this->p[$damager->getName()]["qm"] = 30;

                                    break;
                                case "다이아":
                                    $damager->sendMessage($m . "저희 사치 좀 부리게 광산에서 다이아 30개 캐주세요");
                                    $this->p[$damager->getName()]["qm"] = 30;

                                    break;
                            }
                            break;
                        }
                    }
                    /*
                     * else if ($a +1 == $this->npc[$name]["number"] ){
                     * $damager->sendMessage($m."나의 npc가 아닙니다");
                     */
                }
            }
        }
    }
}
    public function job(){
       $r = mt_rand(1, 100);
       if ($r <= 70){
           return 1;
       }
       if ($r <= 90 && 70 < $r ){
           return 2;
       }
       if (90 < $r && $r <= 100) {
           return 3;
       }
    }
    
    public function move(EntityMotionEvent$event){
        $entity = $event->getEntity();
        if ($entity instanceof \pocketmine\entity\Villager){
            $event->setCancelled(true);
        }
    }
    
    public function onCommand(CommandSender $sender, Command $command,  string $label, array $args):bool{
        $m = $this->m;
        
        $name = $sender->getName();
        if ($command->getName() == "전쟁"){
            if(isset($args[0])){
                $sender->sendMessage("잘 생각해봐");
            }
            else {
            if ($args[0] == "시작"){
                
                
                
                if ($this->war["w"] == "false" and $sender->isOp()){
                    $this->getServer()->broadcastMessage($this->m."영토점령의 시작입니다.§a피터지게 싸우세요");
                    $this->getServer()->broadcastMessage($this->m."이제  pvp가 어디에서든지 활성화 됩니다§a피터지게 싸우세요");
                    
                    $this->war["w"] = "true";
                    return true;
                }
                else{
                    $sender->sendMessage($this->m." 이미 전쟁중입니다");
                    return true;
                }
            }
            if ($this->war == "true" and $sender->isOp()){
                if ($args[0] == "종료"){
                    $this->getServer()->broadcastMessage($this->m."영토점령의 종료입니다.싸움을 멈추세요");
                    $this->getServer()->broadcastMessage($this->m."이제  pvp가 어디에서든지 비활성화 됩니다");
                    $this->war["w"] = "false";
                    return true;
                }
                
            }
            else {
                $sender->sendMessage($this->m." 이미 평화롭습니다");
            }
        }
        }if ($command->getName() == "야간투시"){
            $sender->addEffect(new EffectInstance(Effect::getEffect(16) , 20*60*10000 , 0));
            return true;
        }
        if ($command->getName() == "연합"){    
            if(!isset ($args[0])){
                $sender->sendMessage($m."========================================");
                $sender->sendMessage($m."/연합 생성 [이름 ]");
                $sender->sendMessage($m."/연합 신청 목록");
                $sender->sendMessage($m."/연합 초대 [이름 ]");
                $sender->sendMessage($m."/연합 강퇴 [이름 ]");
                $sender->sendMessage($m."/연합 효과 목록");
                $sender->sendMessage($m."/연합 효과 [이름 ]");
                $sender->sendMessage($m."/연합 강화");
                $sender->sendMessage($m."========================================");
                return true;
            }
            else{
                switch ($args[0]){
                    case "생성":
                        if (isset($args[1]) and $this->p[$name]["sinbon"] == "영주" and $sender->getInventory()->contains(Item::get(388 , 0, 500))  && $args[1] != "UNKNOWN"){
                            $this->union[$args[1]] = [];
                            $this->union[$args[1]]["name"] = $args[1];
                            $this->union[$args[1]]["people"] = array($name );
                            $this->union[$args[1]]["max"] = 5;
                            $this->union[$args[1]]["king"] = $name;
                            $this->union[$args[1]]["level"] = 1;
                            $this->union[$args[1]]["exp"] = 0;
                            $this->union[$args[1]]["landcount"] = 0;
                            $this->union[$args[1]]["effect"] = [];
                            array_push($this->l["union"] , $args[1]);
                            $this->p[$name]["union"] = $args[1];
                            $this->p[$name]["sinbon"] = "연합장";
                            
                            $sender->getInventory()->removeItem(Item::get(388,0,500));
                        }
                        else if (!isset($args[1])){
                            $sender->sendMessage($this->m." 연합이름을 적어주세요");
                        }
                        else if (!$sender->getInventory()->contains(Item::get(388 , 0, 500))){
                            $sender->sendMessage($this->m."에매할드 500개 이상이 인벤에 있으셔야합니다");
                            
                        }
                        else if ($this->p[$name]["sinbon"] !== "영주"){
                            $sender->sendMessage($this->m."신분이 영주여야만 합니다.");
                            
                        }
                        return true;
                        break;
                    case "삭제":
                        if ($this->p[$name]["sinbon"] == "연합장"){
                            unset($this->union[$this->p[$name]["union"]]);
                            for ($p = 0; $p< count($this->union[$this->p[$name]["union"]]["people"]); $p++ ){
                                $this->p[$this->union[$this->p[$name]["union"]]["people"][$p]]["union"] = "무소속";
                            }
                        }
                        return true;
                        break;
                    case "초대":
                        if ($this->p[$name]["sinbon"] == "연합장" and isset($args[1] ) && count( $this->union[$this->p[$name]["union"]]["people"]) < $this->union[$this->p[$name]["union"]]["max"] ) {
                            foreach ($sender->getServer()->getOnlinePlayers() as $player){
                                if ($player->getName() == $args[1]){
                                    if ($this->p[$player->getName()]["union"] == "무소속"){
                                        $player->sendMessage($this->m .$this->union[$this->p[$name]["union"]]["name"]." 으로 부터 초대받았습니다. 수락을 원하시면 /연합 수락 [연합이름] 을 해주세요");
                                        array_push($this->p[$player->getName()]["list"],$this->p[$name]["union"] );
                                        return true;
                                        break;
                                    }
                                    else {
                                        $sender->sendMessage($this->m." 이 블레이어는 이미 소속이 있습니다.");
                                       return true;
                                        break;
                                    }
                                    
                                }
                                else {
                                    $sender->sendMessage($m." 현제 활동중인 유저가 아닙니다.");
                                    return true;
                                }
                                
                            }
                            
                        }
                        else {
                            $sender->sendMessage($this->m."연합원의 한계는 ".$this->union[$args[1]]["max"] ." 명 입니다. /연합 강화 로 강화하세요");
                        }
                        return true;
                        break;
                    case "강퇴":
                        if ($this->p[$name]["sinbon"] == "연합장" and isset($args[1] ) and $sender->getName() !== $args[1]) {
                            foreach ($sender->getServer()->getOnlinePlayers() as $player){
                                if ($player->getName() == $args[1]){
                                    for ($a = 0 ; $a < count($this->untion[$this->p[$name]["union"]]["poeple"]); $a++  ){
                                            if ($this->union[$this->p[$name]["union"]]["people"][$a] == $args[1]){
                                                unset($this->union[$this->p[$name]["union"]]["people"][$a]);
                                                return true;
                                                break;
                                            }
                                            
                                            
                                        
                                    }return true;
                                    
                                }
                            }
                        }
                        return true;
                        break;
                    case "수락":
                        if ($this->p[$name]["sinbon"] == "무소속"){
                            for ($c = 0 ; $c <count($this->p[$name]["list"]) ; $c++){
                                if ($args[1] == $this->p[$name]["list"][$c]){
                                    $this->p[$name]["union"] = $this->p[$name]["list"][$c];
                                    $this->p[$name]["list"] = [];
                                }
                            }
                            return true;
                        }
                        return true;
                        break;
                    case "목록":
                        if (isset($args[1])){
                            $sender->sendMessage($m."========================================");
                            for ($b = 0+5*$args[1] ; $b < count($this->l)+5*$args[1]; $b++ ){
                                $sender->sendMessage($m." [".$b+1 ."]".$this->l["union"][$b]);
                            }   
                            $sender->sendMessage($m."========================================");
                            return true;
                        }
                        else {
                            $sender->sendMessage($m."========================================");
                            for ($b = 0 ; $b < count($this->l); $b++ ){
                                $sender->sendMessage($m." [".$b+1 ."]".$this->l["union"][$b]);
                            }
                            $sender->sendMessage($m."========================================");
                            return true;
                        }
                        return true;
                        break;
                    case "신청":
                        if ( $args[1] == "목록" ){
                            $sender->sendMessage($m."========================================");
                            for ($b = 0 ; $b < count($this->p[$name]["list"]); $b++ ){
                                $sender->sendMessage($m." [".$b+1 ."]".$this->p[$name]["list"]);
                            }
                            $sender->sendMessage($m."========================================");
                            
                            return true;
                        } 
                        return true;
                          break;
                    case "효과":
                        if ($args[1] == "목록"){
                            if ($this->p["union"] == "무소속"){
                            $sender->sendMessage($m."========================================");
                            for ($b = 0 ; $b < count($this->union[$this->p["union"]]["effect"]); $b++ ){
                                $sender->sendMessage($m." [".$b+1 ."]".$this->union[$this->p["union"]]["effect"][$b]);
                            }
                            $sender->sendMessage($m."========================================");
                        
                            }
                            else{
                                $sender->sendMessage($this->m."연합이 없습니다");    
                            }
                        }
                        else if ($this->time[$name]["first"] == "0"){
                            $this->time[$name]["first"] = time();
                            $this->Effect($args[1]);
                            break;
                        }
                        else {
                            if(time() - $this->time[$name]["first"] > 5*60){
                                $this->time[$name]["first"] = 0;
                                $this->Effect($args[1]);
                                break;
                            }else{
                                    $sender->sendMessage($this->m."아직 쿨타임이 안 지났습니다.");
                                }
                        }
                    case "강화":
                        if ( $this->p[$name]["sinbon"] == "연합장"){
                            if ($sender->getInventory()->contains(Item::get(388 ,0 ,150* $this->union[$this->p[$name]["union"]]["max"]))){
                                $this->union[$this->p[$name]["union"]]["max"] = $this->union[$this->p[$name]["union"]]["max"]+1;
                                $sender->sendMessage($this->m."강화완료");
                                
                            }
                            else {
                                $sender->sendMessage($this->m."에메랄드가 부족합니다");
                                
                            }
                        }
                        else {
                            $sender->sendMessage($this->m."연합장이 아닙니다");
                            
                        }
                        break;
                    default :
                        $sender->sendMessage($m."========================================");
                        $sender->sendMessage($m."/연합 생성 [이름 ]");
                        $sender->sendMessage($m."/연합 신청 목록");
                        $sender->sendMessage($m."/연합 초대 [이름 ]");
                        $sender->sendMessage($m."/연합 강퇴 [이름 ]");
                        $sender->sendMessage($m."/연합 효과 목록");
                        $sender->sendMessage($m."/연합 효과 [이름 ]");
                        $sender->sendMessage($m."/연합 강화");
                        $sender->sendMessage($m."========================================");
                        return true;
                        break;
            
                }
            }
        }
    }
    public function Effect($args){
        if ($args == "신속"){
            if (isset($this->union[$this->p["union"]]["effect"][0]) ){
                $sender->addEffect(new EffectInstance(Effect::getEffect(1) , 20*60 , 0));
            }
        }
        else if ($args == "성급함"){
            if (isset($this->union[$this->p["union"]]["effect"][2]) ){
                $sender->addEffect(new EffectInstance(Effect::getEffect(3) , 20*60 , 0));
            }
        }
        
        else if ($args== "힘"){
            if (isset($this->union[$this->p["union"]]["effect"][3]) ){
                $sender->addEffect(new EffectInstance(Effect::getEffect(5) , 20*60 , 0));
            }
        }
        else if ($args == "저항"){
            if (isset($this->union[$this->p["union"]]["effect"][4] )){
                $sender->addEffect(new EffectInstance(Effect::getEffect(11) , 20*60 , 0));
            }
        }
        else if ($args == "점프강화"){
            if (isset($this->union[$this->p["union"]]["effect"][1] )){
                $sender->addEffect(new EffectInstance(Effect::getEffect(8) , 20*60 , 0));
            }
        }
        else if ($args == "신속2"){
            if (isset($this->union[$this->p["union"]]["effect"][5] )){
                $sender->addEffect(new EffectInstance(Effect::getEffect(1) , 20*60 , 1));
            }
        }
        else if ($args == "성급함2"){
            if (isset($this->union[$this->p["union"]]["effect"][7] )){
                $sender->addEffect(new EffectInstance(Effect::getEffect(3) , 20*60 , 1));
            }
        }
        else if ($args == "점프강화2"){
            if (isset($this->union[$this->p["union"]]["effect"][6] )){
                $sender->addEffect(new EffectInstance(Effect::getEffect(3) , 20*60 , 1));
            }
        }
        
        else {
            $sender->sendMessage($m."========================================");
            $sender->sendMessage($m."/연합 생성 [이름 ]");
            $sender->sendMessage($m."/연합 신청 목록");
            $sender->sendMessage($m."/연합 초대 [이름 ]");
            $sender->sendMessage($m."/연합 강퇴 [이름 ]");
            $sender->sendMessage($m."/연합 효과 목록");
            $sender->sendMessage($m."/연합 효과 [이름 ]");
            $sender->sendMessage($m."========================================");
            
        }
    }
    
    public function quest(){
        switch (mt_rand(1,6)){
            case 1:
                return "감자";
                
                break;
            case 2:
                return "당근";
                break;
            case 3:
                return "감자";
                break;
            case 4:
                return "돌조각상";
                break;
            case 5:
                return "돈부족"  ;
                break;
            
            case 6:
                return "다이아";
                break;
        }
    }
    public function getsinbon( $name){
        return $this->p[$name]["sinbon"];
    }
    public function getlevel( $name){
        return $this->p[$name]["level"];
    }
    public function getunion( $name){
        return $this->p[$name]["union"];
    }
    public function getquest($name){
        return $this->p[$name]["quest"];
    }
    public function setExp(){
        foreach ($this->getOwner()->getServer()->getOnlinePlayers() as $player){
            if ($this->p[$player->getName()]["union"] == "무소속"){
                $this->union[$this->p[$player->getName()]["union"]]["exp"] = $this->union[$this->p[$player->getName()]["union"]]["exp"]+10;
                if ($this->union[$this->p[$player->getName()]["union"]]["exp"] <= $this->union[$this->p[$player->getName()]["union"]]["level"]*17000){
                    $this->union[$this->p[$player->getName()]["union"]]["level"] = $this->union[$this->p[$player->getName()]["union"]]["level"]+1;
                    $this->union[$this->p[$player->getName()]["union"]]["exp"] = 0;
                    ++$this->union[$this->p[$player->getName()]["union"]]["landcount"];
                    $this->openab($this->p[$player->getName()]["union"]);
                    $this->getServer()->broadcastMessage($this->m.$this->p[$player->getName()]["union"]."연합이 레벨업 하였습니다 모두 축하해주세요");
                }
            }
        }
    }
    public function openab($name){
        switch ($this->union[$name]["level"]){
            case "2":
                array_push($this->union[$name]["effect"],"신속");
                break;
            case "3":
                array_push($this->union[$name]["effect"],"점프강화");
                break;
            case "4":
                array_push($this->union[$name]["effect"],"성급함");
                break;
            case "5":
                array_push($this->union[$name]["effect"],"힘");
                break;
            case "6":
                array_push($this->union[$name]["effect"],"저항");
                break;
            case "7":
                array_push($this->union[$name]["effect"],"신속2");
                break;
            case "8":
                array_push($this->union[$name]["effect"],"점프강화2");
                break;
            case "9":
                array_push($this->union[$name]["effect"],"성급함2");
                break;
            default:
                break;
        }
    }
    public function setHourExp(){
        for ($a = 0 ; $a < count($this->l["union"]); $a++){
        $this->union[$this->l["union"][$a]]["exp"] = $this->union[$this->l["union"][$a]]["exp"]+$this->union[$this->l["union"][$a] ]["landcount"]*100;
        if ($this->union[$this->l["union"][$a]]["exp"] <= $this->union[$this->l["union"][$a]]["level"]*17000){
            $this->union[ $this->l["union"][$a] ]["level"] = $this->union[$this->l["union"][$a]]["level"]+1;
            $this->union[$this->l["union"][$a] ]["exp"] = 0;
            $this->getServer()->broadcastMessage($this->m.$this->p[$player->getName()]["union"]."연합이 레벨업 하였습니다 모두 축하해주세요");
        }
        }
    }
    public function exp($player){
        if ($this->p[$player->getName()]["union"] != "무소속"){
        return $this->union[$this->p[$player->getName()]["union"]]["exp"];
        }
        else {
            return "무소속";
        }
    }
    public function maxexp($player){
        if ($this->p[$player->getName()]["union"] != "무소속"){
            return $this->union[$this->p[$player->getName()]["union"]]["level"]*17000;

        }
        else {
            return "무소속";
        }
        
    }
    public function setMoney(){
        foreach ($this->getServer()->getOnlinePlayers() as $player){
            $name = $player->getName();
            $a =$this->npc[$name]["NPCnumber"];
            if ($a !== 0){
            for  ($b = 0; $b <$a; $b++ ){
            
                $this->npc[$name][$b]["money"] = $this->npc[$name][$b]["money"]+ 
                mt_rand($this->npc[$name][$b]["level"]*$this->npc[$name][$b]["job"]*100 ,
                $this->npc[$name][$b]["level"]*$this->npc[$name][$b]["job"]*150);
            }
            }
        }
    }
    public function onDisable(){
        $this->save();
    }
}

class task1 extends PluginTask{

    public function onRun(int $currentTick){
        foreach ($this->getOwner()->getServer()->getOnlinePlayers() as $player){
            $name = $player->getName();
            $quest = $this->getOwner()->getquest($name);
            $소속 = $this->getOwner()->getunion($name);
            $신분 = $this->getOwner()->getsinbon($name);
            $player->sendTip(str_repeat( " ", 53 )."§b["."§f영주 정보"."§b]"."\n"
                .str_repeat( " ", 53 )."§b닉네임: §f".$name."\n"
                .str_repeat( " ", 53 )."§b퀘스트: §f".$quest." §b소속: §f".$소속."\n"
                .str_repeat(" ", 53)."§b신분: §f".$신분." §b연합 EXP: §f".$this->getOwner()->exp($player)." / ".$this->getOwner()->maxexp($player));
                
        }
    }
}
class EXP extends PluginTask{
    public function onRun(int $currentTick){
            $this->getOwner()->setExp();
            $this->getServer()->broadcastMessage($this->m."연합원 관련 경험치가 들어 왔습니다");
            
    }
}
class HEXP extends PluginTask{
    public function onRun(int $currentTick){
        $this->getOwner()->setHourExp();
        $this->getServer()->broadcastMessage($this->m."연합 영토 관련 경험치가 들어 왔습니다");
        
    }
}
class money extends PluginTask{
    public function onRun(int $currentTick){
        $this->getOwner()->setMoney();
        $this->getOwner()->save();
    }
}

