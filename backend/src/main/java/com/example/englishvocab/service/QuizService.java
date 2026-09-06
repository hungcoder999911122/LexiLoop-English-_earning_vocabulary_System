package com.example.englishvocab.service;

import com.example.englishvocab.dto.ApiDtos.*;
import com.example.englishvocab.entity.*;
import com.example.englishvocab.repository.*;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.stream.Collectors;

@Service
@Transactional
public class QuizService {
    private final QuizRepository quizRepository;
    private final QuestionRepository questionRepository;
    private final AnswerRepository answerRepository;
    private final QuizResultRepository resultRepository;
    private final QuizResultDetailRepository detailRepository;
    private final TopicRepository topicRepository;
    private final VocabularyRepository vocabularyRepository;
    private final VocabularySenseRepository senseRepository;

    public QuizService(QuizRepository quizRepository, QuestionRepository questionRepository,
                       AnswerRepository answerRepository, QuizResultRepository resultRepository,
                       QuizResultDetailRepository detailRepository, TopicRepository topicRepository,
                       VocabularyRepository vocabularyRepository, VocabularySenseRepository senseRepository) {
        this.quizRepository = quizRepository;
        this.questionRepository = questionRepository;
        this.answerRepository = answerRepository;
        this.resultRepository = resultRepository;
        this.detailRepository = detailRepository;
        this.topicRepository = topicRepository;
        this.vocabularyRepository = vocabularyRepository;
        this.senseRepository = senseRepository;
    }

    @Transactional
    public QuizDto start(User user, QuizStartRequest req) {
        Topic topic = topicRepository.findById(req.topicId())
                .orElseThrow(() -> new IllegalArgumentException("Không tìm thấy chủ đề"));
        List<Vocabulary> pool = new ArrayList<>(vocabularyRepository.findByTopic_TopicIDOrderByWordAsc(topic.getTopicID()));
        if (pool.size() < 4) {
            throw new IllegalArgumentException("Chủ đề cần tối thiểu 4 từ để tạo quiz 4 lựa chọn");
        }
        Collections.shuffle(pool);
        int n = req.numQuestions() == null ? 8 : Math.min(req.numQuestions(), pool.size());
        List<Vocabulary> picked = pool.subList(0, n);

        Quiz quiz = new Quiz();
        quiz.setTopic(topic);
        quiz.setCreatedBy(user);
        quiz.setTitle("Quiz · " + topic.getTopicName());
        quiz.setNumQuestions(n);
        quizRepository.save(quiz);

        List<QuizQuestionDto> qDtos = new ArrayList<>();
        List<String> allMeanings = pool.stream()
                .map(this::firstMeaning)
                .collect(Collectors.toList());

        int idx = 0;
        for (Vocabulary v : picked) {
            String correct = firstMeaning(v);
            Question q = new Question();
            q.setQuiz(quiz);
            q.setVocabulary(v);
            boolean enToVi = idx % 2 == 0;
            q.setQuestionType(enToVi ? "EN_TO_VI" : "VI_TO_EN");
            q.setQuestionText(enToVi ? ("Nghĩa của \"" + v.getWord() + "\" là?") : ("Từ nào nghĩa là: " + correct + " ?"));
            questionRepository.save(q);

            List<String> distractors = new ArrayList<>(allMeanings);
            distractors.remove(correct);
            Collections.shuffle(distractors);

            List<QuizAnswerOption> options = new ArrayList<>();
            if (enToVi) {
                List<String> texts = new ArrayList<>();
                texts.add(correct);
                texts.addAll(distractors.stream().limit(3).toList());
                Collections.shuffle(texts);
                for (String t : texts) {
                    Answer a = saveAnswer(q, t, t.equals(correct));
                    options.add(new QuizAnswerOption(a.getAnswerID(), a.getAnswerText()));
                }
            } else {
                List<Vocabulary> words = new ArrayList<>(pool);
                words.remove(v);
                Collections.shuffle(words);
                List<Vocabulary> choices = new ArrayList<>();
                choices.add(v);
                choices.addAll(words.subList(0, 3));
                Collections.shuffle(choices);
                for (Vocabulary w : choices) {
                    Answer a = saveAnswer(q, w.getWord(), w.getVocabularyID().equals(v.getVocabularyID()));
                    options.add(new QuizAnswerOption(a.getAnswerID(), a.getAnswerText()));
                }
            }
            qDtos.add(new QuizQuestionDto(q.getQuestionID(), q.getQuestionText(), q.getQuestionType(), options));
            idx++;
        }
        return new QuizDto(quiz.getQuizID(), quiz.getTitle(), qDtos);
    }

    @Transactional
    public QuizResultDto submit(User user, QuizSubmitRequest req) {
        Quiz quiz = quizRepository.findById(req.quizId()).orElseThrow(() -> new IllegalArgumentException("Quiz không tồn tại"));
        List<Question> questions = questionRepository.findByQuiz_QuizID(quiz.getQuizID());
        int score = 0;
        QuizResult result = new QuizResult();
        result.setQuiz(quiz);
        result.setUser(user);
        result.setTotal(questions.size());
        result.setScore(0);
        result.setDurationSec(req.durationSec());
        resultRepository.save(result);

        for (Question q : questions) {
            Integer selectedId = req.answers() == null ? null : req.answers().stream()
                    .filter(a -> a.questionId().equals(q.getQuestionID()))
                    .map(QuizSubmitItem::answerId)
                    .findFirst().orElse(null);
            Answer selected = selectedId == null ? null : answerRepository.findById(selectedId).orElse(null);
            boolean ok = selected != null && Boolean.TRUE.equals(selected.getCorrect());
            if (ok) score++;
            QuizResultDetail d = new QuizResultDetail();
            d.setResult(result);
            d.setQuestion(q);
            d.setSelectedAnswer(selected);
            d.setCorrect(ok);
            detailRepository.save(d);
        }
        result.setScore(score);
        resultRepository.save(result);
        double percent = questions.isEmpty() ? 0 : (score * 100.0 / questions.size());
        return new QuizResultDto(result.getResultID(), score, questions.size(), Math.round(percent * 10) / 10.0, req.durationSec());
    }

    public List<QuizResult> history(User user) {
        return resultRepository.findByUser_UserIDOrderByTakenAtDesc(user.getUserID());
    }

    private Answer saveAnswer(Question q, String text, boolean correct) {
        Answer a = new Answer();
        a.setQuestion(q);
        a.setAnswerText(text);
        a.setCorrect(correct);
        return answerRepository.save(a);
    }

    private String firstMeaning(Vocabulary v) {
        return senseRepository.findByVocabulary_VocabularyID(v.getVocabularyID())
                .stream().findFirst().map(VocabularySense::getMeaningVi).orElse(v.getWord());
    }
}
