# COMMANDS

- pod - это центральный объект в k8s (Deployment, StatefulSet, Job - это объекты k8s, управляющие pod)
- Pod = Containers (но в идеале 1 Pod = 1 Container)Node = ServerVolume = Disk
- best k8s namespace: &lt;APP&gt;\_&lt;ENV&gt; (ai_prod, ai_test, ai_dev, proj_2_prod, proj_2_test, proj_2_dev)
- 1 Node принадлежит исключительно 1 Cluster
    - Cluster \[Node, Node, …\] - OneToMany
- k8s объекты могут иметь pod template, и его структура одинакова для всех объектов k8s
- k8s не любит _ в наименованиях объектов
    - Допустимые символы: \[a-z0-9\\-\]
- В командах, где используется POD_NAME возможен синтаксис:
    - POD_NAME
    - deploy/DEPLOY_NAME
    - sts/STATEFUL_SET_NAME
- HPA берёт проценты нагрузки от resources.requests
    - Плавно создаёт pod replicas от min до max
- (default) Приоритизация - это вытеснение подов с более низким приоритетом
    - pod с guaranteed размещением на Node не подлежат приоритизации
- Service type: None - запись A-type где имя пода связано с PodIP (необходим для работы sts)
- Service type: ClusterIP - это внутри-кластерный статический IP
- Service type: NodePort - это ClusterIP + открытый порт на каждом Node (локально как LoadBalancer)
- pod получает уникальный эфемерный IP в рамках k8s clusterэфемерный - потому что если Deployment пересоздаст pod k8s у него будет новый PodIP
- pod и все его контейнеры имеют одинаковый IP адрес, поэтому порты образов должны быть разными
    - Не существует такого понятия как порт pod, ведь у всех pod контейнеров одинаковыйEach pod container IP = Pod IPПоэтому если у pod несколько контейнеров, нужно понимать, что все они находятся на одном и том же Pod IP и соответственно порты образов(а в будущем и контейнеров) должны быть разными, чтобы не было конфликта портов на том же самом Pod IP
- Отладка pod:
    - kubectl describe … - debug k8s events
    - kubectl logs … - debug container logs itself
- Если сначала создать Service и только потом Deploy,то в Deploy будут доступны ServiceClusterIP ENV того же namespace
- По умолчанию k8s старается полностью заполнить Node уникальными pod,а pod replicas распределить по Nodes
- Топология - отвечает на вопрос: “Где размещать? Node или Zone?“,это абстракция над Node.
- Для размещения pod на конкретном Node нужно 2 требования:1) Со стороны Node: запрет (taint)а в podSpec разрешение (tolerations) на размещение pod2) И со стороны pod: nodeSelector или affinityа в Node необходимый label или другое требование
- **Распределение pods на nodes:**label (pod req) + taint (node req) = гарантия размещения pods на nodes
- **Распределение pod replicas на nodes:**Если указывать affinity и topologySpreadConstraints, то логика: affinity && topologySpreadConstraints
    - affinity
        - podAffinity - предпочитай topology с другим pod (pod label)
        - podAntiAffinity - избегай topology с другим pod (pod label)
    - topologySpreadConstraints
- affinity может распределять deployment pods не только по node,но и по node + другие pod
- Volume ConfigMap - альтернатива созданию собственного, дополненного образадля настройки конфигурацииVolume ConfigMap - READ ONLY
- В StatefulSet у каждой pod replica свой PVC за счёт шаблона PVC (volumeClaimTemplates)
- **У StatefulSet 4 базовых различия от Deployment:**
    - **PodID** (за каждым pod закрепляется стабильный уникальный сетевой ID)
    - +**serviceName**
    - +**volumeClaimTemplates** (чтобы у каждого pod был свой PV)
    - обязателен **terminationGracePeriodSeconds**
- **Внутрикластерный DNS:**
    - **SERVICE DNS:**
        - curl [http://SERVICE_NAME**.**NAMESPACE](http://SERVICE_NAME.NAMESPACE_NAME).svc
        - curl [http://SERVICE_NAME**.**NAMESPACE](http://SERVICE_NAME.NAMESPACE_NAME)
        - curl [http://SERVICE_NAME](http://SERVICE_NAME.NAMESPACE_NAME) если NAMESPACE = default
    - **STATEFUL_SET DNS:**
        - mysql -h STS_NAME**\-**POD_NUMBER**.**SERVICE_NAME**.**NAMESPACE
        - mysql -h STS_NAME**\-**POD_NUMBER**.**SERVICE_NAME если NAMESPACE = default
- Secret можно использовать в ENV & VOLUME:
    - PodSpec.envFrom.secretRef
    - env.valueFrom.secretKeyRef
    - volumes.secret
- ConfigMap можно использовать в ENV & VOLUME:
    - PodSpec.envFrom.configMapRef
    - env.valueFrom.configMapRef
    - volumes.configMap
- Данные о k8s pod можно протаскивать в сам pod в виде ENV:
    - env.valueFrom.fieldRef.fieldPath: metadata.namespace
- Volume (хранилища) бывают:
    - **emptyDir -** аналог docker named volume (volume_name:/container/)
        - межконтейнерное хранилище в рамках pod
        - удаление pod - удаляет данные
            - в то время как у реального docker volume после удаления containerданные volume сохраняются
        - default - Disk, but if “medium: Memory” - RAM
    - **hostPath** - аналог docker bind volume (/host/:/container/)
        - Node path
        - read-write by default
    - **configMap**.name / **secret**.secretName
        - READ ONLY
    - **PV** & **PVC** (удаление pod не удаляет PVC, а значит и PV точно остаётся)
    - **ephemeral** (удаление pod - удаляет PVC, а PV удалиться если RECLAIM POLICY: Delete)
        - по сравнению с emptyDir:
            - emptyDir - это аналог docker named volumeа ephemeral - это PVC & PV
            - ephemeral имеет на много большие пространства
            - ephemeral может иметь storage_class для указания типа хранилища(parameters.type: pd-ssd, …)
    - **platform-dependent** (bad way)
- PVC Retain - облачный дисковый ресурс живёт отдельно от k8s, можно без потерь PVC и PV удалить
    - **best practice - сохранять PV и PVC** через команду kubectl get pv/NAME > pv.yaml …И в каждом файле удалить минимум 2 ненужные строчки: storageClassName x2, fer.uid, “yes”.ТЕПЕРЬ МОЖНО СПОКОЙНО УДАЛЯТЬ КАК PVC, ТАК И PV ДА И ВООБЩЕ ВЕСЬ КЛАСТЕРPV ЖИВЁТ СВОЕЙ ЖИЗНЬЮ КОГДА RECLAIM POLICY: RETAIN
    - Хоть Retain PV действительно сохраняются даже после удалении кластера, но бэкапы PV всё же нужно делать, потому что в моём случае при удалении кластера платформа selectel удалилаRetain PV из-за того, что до удаления кластера я сам не сам не удалил PV через kubectl.Чёрт её знает, что может вытварить облачный сервис с PV Retain.Но в штатном режиме и если удалять pv вручную до удаления кластера, Retain PV естественно сохраняется.
- Job для гарантии выполнения конечной работы (команды)
    - Конечная работа - команда
    - completions (completionMode: Indexed → $JOB_COMPLETION_INDEX)
- k8s CronJob - аналог cron
    - Исполняет Job (команды) по рассписанию
- k8s Deployment - аналог supervisor для непрерывно работающих процессов
    - Бесконечная работа - приложение

- Deployment, StatefulSet, DaemonSet - обслуживают бесконечно-работающие pod
    - у таких объектов есть механизм сопоставления по selector
- Job, CronJob - обслуживают конечно-работающие pod
    - у таких объектов механизм сопоставления по selector автоматический,то есть невозможно указать selector вручную, это делает k8s автоматически
- DaemonSet запускает по 1 pod на каждом Node
    - обычно добавляют readOnly volume.hostPath для анализа логов
- namespace лучше изменять (kubectl config set-context), а не указывать через флаг -n
- best practice - всегда удаляй metadata.namespace
    - **metadata.namespace выше приоритетом** контекста по умолчанию
    - если -n NAMESPACE ≠ metadata.namespace будет явная ошибка
- RollingUpdate:
    - maxSurge: 2
        - 2 реплики с новой версией
    - maxUnavailable: 1
        - 1 unavailable текущей версии
        - то есть не дожидаясь, пока новая версия станет READY
- pod maxUnavailable for updating in updating strategy
- pod maxUnavailable for interrupting (SIGTERM, вытеснение) in kind: PodDisruptionBudget
    - исключает произвольное вытеснение pods, теперь оно с ограничением
    - best practice всегда иметь PodDisruptionBudget на случай обновления Node системой k8s
    - но PodDisruptionBudget не защищает от сбоев Node
- Не headless Service - это всегда балансировка
    - NodePort локально как LoadBalancer, в отличии не балансирующего port-forward**NodePort балансирует среди всех Node, хоть я и обращаюсь по NodeIP (NodeIP→ServiceIP)**
- **requests.memory - RAM**
- **requests.ephemeral-storage - DISK (emptyDir by default DISK + writable container layer)**
    - Просто emptyDir с настройкой medium: “Memory” - это уже RAM (tmpfs)а раз это RAM (не DISK), то в ephemeral-storage не входит

- **Pod security by kind: Namespace (PSA - встроенный в k8s контроллер допуска)**best security context:
    
    ```
    securityContext:
     runAsNonRoot: true
     runAsUser:  65534 # 65534 === nobody
     runAsGroup: 65534 # 65534 === nobody
     allowPrivilegeEscalation: false
     capabilities:
       drop: ["ALL"]
    ```
    
    - Гарантия использования securityContext - политика безопасности podПолитика безопасности pod - это коллекция контроллеров доступаController Admissions = webhook (есть как изменяющие, так и проверяющие)Объект Namespace создаёт namespace и применяет к нему контроллеры доступа (pod security)
        
        ```
        apiVersion: v1
        kind: Namespace
        metadata:
          name: team1
          labels:
            pod-security.kubernetes.io/enforce: restricted      # policy-name
                                                                  # privileged
                                                                  # baseline
                                                                  # restricted
            pod-security.kubernetes.io/enforce-version: 'v1.28' # policy-version
        ```
        
- **User|Pod security by kind: Role (создание каких объектов k8s разрешены, запрет смены ns):**
    - Объекты доступов уровня namespace - Role, RoleBinding (User, Group)for namespace-scope resource
    - Объекты доступов уровня cluster - ClusterRole, ClusterRoleBinding (User, Group)for cluster-scope resource
        
        - StorageClass
        - PriorityClass
        - PV
        
        Если pod использует kubectl он может оперировать над любым k8s объектомэто можно ограничить создав kind: ServiceAccount
        
        ```
        apiVersion: v1
        kind: ServiceAccount
        metadata:
          name: sa
          namespace: app
        ```
        
        - В PodSpec.serviceAccountName
- Чтобы можно было писать в папку не от пользователя root, а от root group:
    
    ```
    RUN chgrp -R 0 logs && chmod g+rwX -R logs
    ```
    
- kind: NetworkPolicy - регулирование трафика (по умолчанию pod-based), запрещающей логики нет.
    - Ingress - to pod traffic
    - Egress - from pod traffic
    - Пока ничего не разрешено - разрешено всё
    - Если что-то разрешено - всё остальное запрещено
    - Имеет значение указание ключа и значения
        
        ```
                - ipBlock:
                    cidr: '0.0.0.0/0' # Allow all traffic, all namespaces, all pods
                    expect: [...]
        
                # namespaceSelector: {}  - Allow any namespace traffic
                # But if commented       - Allow only this namespace traffic
                - namespaceSelector: {}
                
                # podSelector: {}        - Allow any pod traffic
                # But if commented       - Allow only this pod traffic
                - podSelector: {}
        ```
        
        > Обращение к поду напрямую через PodIP и обращение через Service для этих подов
        > 
        > ЭТО РАЗНЫЕ ВИДЫ ТРАФИКА
        > 
        > и если разрешить только podSelector{} - все pod в текущем ns могут взаимодействовать
        > 
        > это означает, что именно прямое обращение к pod в текущем ns - разрешено
        > 
        > а обращение из service того же namespace - запрещено
        > 
        > для разрешения нужен ipBlock.cidr: '10.0.0.0/8'
        
        > Если разрешил ipBlock.cidr: '0.0.0.0/0' - то буквально это настройка по умолчанию,
        > 
        > которая разрешает всё
        
        ```plaintext
        Универсально:
        - podSelector: {} # allow traffic only in current namespace for all pods
        ```
        
        ```plaintext
        Специфично:
        - namespaceSelector:
            matchLabels:
              kubernetes.io/metadata.name: default # для default namespace все pod
          podSelector: {}
        ```
        

---

```plaintext
kubectl create -f deploy.yaml
kubectl apply -f deploy.yaml --annotation='kubernetes.io/change-cause=...'
kubectl delete sts,svc,configmap --all
kubectl create namespace NAMESPACE # но лучше через kind: Namespace
                                   # ведь заодно можно указать ещё и pod-security

kubectl port-forward POD_NAME             HOST_PORT:CONTAINER_PORT
kubectl port-forward deploy/DEPLOY_NAME   HOST_PORT:CONTAINER_PORT
kubectl port-forward service/SERVICE_NAME HOST_PORT:CONTAINER_PORT

kubectl describe pod POD_NAME # debug k8s events

# debug docker container
kubectl logs -f -p deploy/DEPLOY_NAME --all-containers=true
kubectl logs --tail 30 --selector ...

watch -d kubectl get pods --selector=pod=app-pod-label
watch -d kubectl get pod,deploy,ingress,svc,hpa,secret,ns --all-namespaces -o wide
kubectl get ingress INGRESS_NAME -o=jsonpath="{.metadata}" -o=yaml

kubectl exec -it POD_NAME -- sh
kubectl exec -it deploy/DEPLOY_NAME -- sh

kubectl set image DEPLOY_NAME CONTAINER_NAME=IMAGE:TAG

# получает контекст
kubectl config get-contexts
# переключает контекст
kubectl config use-context CONTEXT_NAME
# изменяет контекст
kubectl config set-context --current --namespace=NAMESPACE
# current namespace
kubectl config view --minify -o=jsonpath={.contexts[0].context.namespace} 
kubectl config view --minify -o=jsonpath={..namespace} 

kubectl rollout restart deploy DEPLOY_NAME
kubectl rollout history deploy DEPLOY_NAME
kubectl rollout undo deploy DEPLOY_NAME --to-revision=N
kubectl rollout status deploy DEPLOY_NAME

kubectl annotate deploy DEPLOY_NAME kubernetes.io/change-cause="..."

kubectl top pod

kubectl scale deploy DEPLOY_NAME --replicas=6

kubectl cordon NODE_NAME # помечает Node как Unshcedulable
kubectl drain NODE_NAME  # помечает Node как Unshcedulable + удаляет все pod
# если есть kind: PodDisruptionBudget, то удаляет pod не сразу, а по этому объекту

kubectl autoscale deployment DEPLOY_NAME --cpu-percent='10%' --min=10 --max=20

kubectl create secret generic SECRET_NAME --from-file=K=FP --dry-run=client -o yaml
kubectl create secret tls SECRET_NAME --cert C.CRT --key K.KEY
kubectl create secret docker-registry app-secret-name \
  --docker-email=email@email.ru \
  --docker-username=token \
  --docker-password=... \
  --docker-server=cr.selcloud.ru

# Ограничение размещения pod на Node, pod сможет разместиться на Node если есть KEY=VALUE
kubectl taint node NODE_NAME KEY=VALUE:NoSchedule
kubectl taint node NODE_NAME KEY-

# manifest affinity/selector
kubectl label node NODE_NAME KEY=VALUE

kubectl edit deploy DEPLOY_NAME
kubectl edit storageclass STORAGE_CLASS_NAME

kubectl patch storageclass STORAGE_CLASS_NAME -p '{"metadata": {"annotations": {"storageclass.kubernetes.io/is-default-class":"false"}}}'

kubectl run --rm -it --image=IMAGE_NAME POD_NAME
```

```plaintext
minikube start / stop / delete
minikube start --driver=docker --cpus 5 --memory 10000  --nodes 3
minikube delete --all --purge

minikube node add

minikube image load/rm IMAGE:TAG

minikube addons enable metrics-server # kubectl top pod

minikube ssh # like docker exec, kubectl exec
```

| livenessProbe | readinessProbe |
| --- | --- |
| RUNNING | READY/AVAILABLE |
| Родился | но ещё не работает |
| pod должен быть перезапущен, когда проблема в pod | Service не балансирует нагрузку на не READY pod, ждём пока очнётся |